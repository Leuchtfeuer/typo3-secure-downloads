<?php

declare(strict_types=1);

/*
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\SecureDownloads\Resource;

use Leuchtfeuer\SecureDownloads\Cache\DecodeCache;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\AbstractToken;
use Leuchtfeuer\SecureDownloads\MimeTypes;
use Leuchtfeuer\SecureDownloads\Registry\CheckRegistry;
use Leuchtfeuer\SecureDownloads\Registry\TokenRegistry;
use Leuchtfeuer\SecureDownloads\Resource\Event\AfterFileRetrievedEvent;
use Leuchtfeuer\SecureDownloads\Resource\Event\BeforeReadDeliverEvent;
use Leuchtfeuer\SecureDownloads\Resource\Event\OutputInitializationEvent;
use Leuchtfeuer\SecureDownloads\Security\AbstractCheck;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

class FileDelivery implements SingletonInterface
{
    protected AbstractToken $token;

    public function __construct(
        protected ExtensionConfiguration $extensionConfiguration,
        protected EventDispatcherInterface $eventDispatcher,
        protected ResourceFactory $resourceFactory,
        protected Context $context
    ) {}

    /**
     * Delivers the file to the browser if all checks pass and file exists.
     *
     * @param string                 $jsonWebToken The JSON Web token given in the URL
     * @param ServerRequestInterface $request      The server request
     *
     * @return ResponseInterface Either the valid file as a stream or an error response
     *
     * @throws PageNotFoundException|ResourceDoesNotExistException|AspectNotFoundException|AspectPropertyNotFoundException
     */
    public function deliver(string $jsonWebToken, ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->retrieveDataFromJsonWebToken($jsonWebToken)) {
            return $this->getAccessDeniedResponse($request, 'Could not parse token.');
        }

        $this->dispatchOutputInitializationEvent();

        if (!$this->hasAccess() && !$this->isBackendUser()) {
            return $this->getAccessDeniedResponse($request, 'Access check failed.');
        }

        if (!$this->isBackendUser() && $this->token->getPage() === 0 && $this->token->getUser() === 0) {
            return $this->getAccessDeniedResponse($request, 'Backend link detected.');
        }

        $fileObject = $this->resourceFactory->getFileObjectFromCombinedIdentifier($this->token->getFile());

        if (!$fileObject instanceof AbstractFile) {
            return $this->getFileNotFoundResponse($request, 'File does not exist!');
        }

        if (!$fileObject->getStorage()->checkFileActionPermission('read', $fileObject)) {
            return $this->getFileNotFoundResponse($request, 'File does not exist!');
        }

        $fileName = $fileObject->getName();
        $filePath = $fileObject->getStorage()->getFileForLocalProcessing($fileObject, false);

        $this->dispatchAfterFileRetrievedEvent($filePath, $fileName);

        $fileSize = $fileObject->getSize() ?: (int)filesize($filePath);

        if ($this->extensionConfiguration->isLog()) {
            $this->token->log([
                'fileSize' => $fileSize,
                'mimeType' => $fileObject->getMimeType() ?: (new FileInfo($filePath))->getMimeType()
                    ?: $this->guessMimeTypeByFileExtension($filePath)
                        ?: MimeTypes::DEFAULT_MIME_TYPE,
            ]);
        }

        return $this->deliverFile($fileObject, $filePath, $fileName, $fileSize, $request);
    }

    /**
     * Delivers a FAL File object. Hands off to nginx via X-Accel-Redirect when configured and running behind
     * nginx; otherwise streams the file through PHP, preserving HTTP Range requests (e.g. for HTML5 video
     * seeking) so only the requested byte range is read from disk and sent to the browser.
     *
     * The BeforeReadDeliverEvent is dispatched once, right after the base header is set up but before the
     * response-type-specific headers are added. Those are only applied as defaults for keys the event didn't
     * already set, so a listener may override e.g. Content-Type or Content-Disposition — except Content-Length
     * and Content-Range on a 206 response, which always describe the actual byte range read from disk below.
     */
    protected function deliverFile(ProcessedFile|File $fileObject, string $filePath, string $fileName, int $fileSize, ServerRequestInterface $request): ResponseInterface
    {
        $forceDownload = $this->shouldForceDownload($fileObject->getExtension());
        $outputFunction = $this->extensionConfiguration->getOutputFunction();
        $mimeType = $fileObject->getMimeType() ?: MimeTypes::DEFAULT_MIME_TYPE;
        // it's a capability header telling the client "this resource supports byte-range requests,
        // even though I'm sending you the whole thing right now."
        $header = [
            'Accept-Ranges' => 'bytes',
        ];

        $this->dispatchBeforeReadDeliverEvent($header, $fileName, $mimeType, $forceDownload);

        // nginx serves the file itself (and handles Range requests on its own), so PHP must not stream it.
        if ($this->shouldUseXAccelRedirect($outputFunction)) {
            return $this->getXAccelRedirectResponse($filePath, $header, $fileName, $mimeType, $forceDownload);
        }

        $rangeRequest = RangeRequest::fromHeader($request->getHeaderLine('Range'), $fileSize);

        // Unsatisfiable range -> 416 Range Not Satisfiable
        if (!$rangeRequest->isSatisfiable()) {
            return $this->getRangeNotSatisfiableResponse($rangeRequest, $header);
        }

        // No range requested → full file
        if (!$rangeRequest->isRequested()) {
            return $this->getFullFileResponse($fileObject, $request, $header, $fileName, $forceDownload);
        }

        // Range requested → partial content (206 Partial Content)
        return $this->getRangeResponse($rangeRequest, $fileObject, $request, $filePath, $header, $fileName, $mimeType, $forceDownload);
    }

    /**
     * Checks whether the file should be handed off to nginx via X-Accel-Redirect instead of being streamed by PHP.
     */
    private function shouldUseXAccelRedirect(string $outputFunction): bool
    {
        return $outputFunction === ExtensionConfiguration::OUTPUT_NGINX
            && isset($_SERVER['SERVER_SOFTWARE'])
            && str_starts_with((string)$_SERVER['SERVER_SOFTWARE'], 'nginx');
    }

    /**
     * Builds the response for nginx's X-Accel-Redirect: the actual file body is discarded (nginx serves the file
     * and its Range requests itself), so only the header fields nginx forwards to the client are relevant here
     * (Content-Type, Content-Disposition, Accept-Ranges, Cache-Control, Expires). Content-Type/Content-Disposition
     * are only defaulted here — a listener that already set them via the BeforeReadDeliverEvent wins.
     *
     * @param string   $filePath      The absolute path to the file on disk, appended to the configured protected path
     * @param string[] $header        The header dispatched through the BeforeReadDeliverEvent
     * @param string   $fileName      The name of the file
     * @param string   $mimeType      The mime type of the file
     * @param bool     $forceDownload Whether the file should be forced to download
     */
    private function getXAccelRedirectResponse(string $filePath, array $header, string $fileName, string $mimeType, bool $forceDownload): ResponseInterface
    {
        $header = array_merge([
            'Content-Type' => $mimeType,
            'Content-Disposition' => sprintf('%s; filename="%s"', $forceDownload ? 'attachment' : 'inline', $fileName),
        ], $header);

        // X-Accel-Redirect itself stays forced (it's the internal nginx routing target, not meant to be listener-controlled).
        $header['X-Accel-Redirect'] = sprintf(
            '%s/%s',
            rtrim($this->extensionConfiguration->getProtectedPath(), '/'),
            ltrim($filePath, '/')
        );
        // Accept-Ranges is not applied from $header, since Nginx already set it by default
        unset($header['Accept-Ranges']);

        return new Response('php://temp', 200, $header);
    }

    /**
     * Builds the 416 Range Not Satisfiable response for a Range header that cannot be fulfilled. Content-Range is
     * only defaulted here — a listener that already set it via the BeforeReadDeliverEvent wins.
     *
     * @param RangeRequest $rangeRequest The parsed and unsatisfiable Range request
     * @param string[]     $header       The header dispatched through the BeforeReadDeliverEvent
     */
    private function getRangeNotSatisfiableResponse(RangeRequest $rangeRequest, array $header): ResponseInterface
    {
        $header = array_merge([
            'Content-Range' => $rangeRequest->getUnsatisfiableContentRange(),
        ], $header);

        return new Response('php://temp', 416, $header);
    }

    /**
     * Streams the full file body through PHP via the storage's streamFile() (no Range requested).
     *
     * @param ProcessedFile|File     $fileObject    The file to deliver
     * @param ServerRequestInterface $request       The server request
     * @param string[]               $header        The header dispatched through the BeforeReadDeliverEvent
     * @param string                 $fileName      The name of the file
     * @param bool                   $forceDownload Whether the file should be forced to download
     */
    private function getFullFileResponse(ProcessedFile|File $fileObject, ServerRequestInterface $request, array $header, string $fileName, bool $forceDownload): ResponseInterface
    {
        $response = $fileObject
            ->getStorage()
            ->streamFile(
                $fileObject,
                $forceDownload,
                $fileName
            );

        // Content-Length is not applied from $header, since streamFile() already set it to the actual file size read from disk below.
        unset($header['Content-Length']);
        foreach ($header as $headerName => $headerValue) {
            $response = $response->withHeader($headerName, $headerValue);
        }

        ob_end_clean();

        if ($request->getMethod() === 'HEAD') {
            return $response->withBody(new Stream('php://temp'));
        }

        return $response;
    }

    /**
     * Builds the 206 Partial Content response, streaming only the requested byte range from disk. Content-Type,
     * Content-Disposition, Last-Modified and Cache-Control are only defaulted here — a listener that already set
     * them via the BeforeReadDeliverEvent wins. Content-Length/Content-Range are not, since they must match the
     * actual byte range read from disk below.
     *
     * @param RangeRequest           $rangeRequest  The parsed and satisfiable Range request
     * @param ProcessedFile|File     $fileObject    The file to deliver
     * @param ServerRequestInterface $request       The server request
     * @param string                 $filePath      The absolute path to the file on disk
     * @param string[]               $header        The header dispatched through the BeforeReadDeliverEvent
     * @param string                 $fileName      The name of the file
     * @param string                 $mimeType      The mime type of the file
     * @param bool                   $forceDownload Whether the file should be forced to download
     */
    private function getRangeResponse(RangeRequest $rangeRequest, ProcessedFile|File $fileObject, ServerRequestInterface $request, string $filePath, array $header, string $fileName, string $mimeType, bool $forceDownload): ResponseInterface
    {
        $header = array_merge([
            'Content-Disposition' => sprintf('%s; filename="%s"', $forceDownload ? 'attachment' : 'inline', $fileName),
            'Content-Type' => $mimeType,
            'Last-Modified' => gmdate('D, d M Y H:i:s', $fileObject->getModificationTime()) . ' GMT',
            'Cache-Control' => '',
        ], $header);

        // Content-Length/Content-Range forced, since those must match the actual bytes RangeStream sends
        $header = array_merge($header, [
            'Content-Length' => (string)$rangeRequest->getLength(),
            'Content-Range' => $rangeRequest->getContentRange(),
        ]);

        if ($request->getMethod() === 'HEAD') {
            return new Response('php://temp', 206, $header);
        }

        return new Response(new RangeStream($filePath, $rangeRequest->getStart(), $rangeRequest->getLength()), 206, $header);
    }

    /**
     * Get data from cache if JWT was decoded before. If not, decode given JWT.
     *
     * @param string $jsonWebToken The JSON Web token
     *
     * @return bool True, when the token can be decoded, false when an exception was thrown
     */
    protected function retrieveDataFromJsonWebToken(string $jsonWebToken): bool
    {
        if (DecodeCache::hasCache($jsonWebToken)) {
            $this->token = DecodeCache::getCache($jsonWebToken);
        } else {
            try {
                $this->token = TokenRegistry::getToken();
                $this->token->decode($jsonWebToken);
                DecodeCache::addCache($jsonWebToken, $this->token);
            } catch (\Exception) {
                return false;
            }
        }

        return true;
    }

    /**
     * Triggers TYPO3's 403 action to output the configured 403 page.
     *
     * @param ServerRequestInterface $request The server request
     * @param string                 $reason  The reason phrase
     *
     *
     * @throws PageNotFoundException
     */
    protected function getAccessDeniedResponse(ServerRequestInterface $request, string $reason): ResponseInterface
    {
        return GeneralUtility::makeInstance(ErrorController::class)->accessDeniedAction(
            $request,
            $reason,
            ['code' => PageAccessFailureReasons::ACCESS_DENIED_PAGE_NOT_RESOLVED]
        );
    }

    /**
     * Triggers TYPO3's 404 action to output the configured 404 page.
     *
     * @param ServerRequestInterface $request The server request
     * @param string                 $reason  The reason phrase
     *
     *
     * @throws PageNotFoundException
     */
    protected function getFileNotFoundResponse(ServerRequestInterface $request, string $reason): ResponseInterface
    {
        return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
            $request,
            $reason,
            ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
        );
    }

    /**
     * Checks all registered checks for user access.
     *
     * @return bool True, when the user has access to the file and all checks passed successfully, false if not
     *
     * @throws AspectNotFoundException
     */
    protected function hasAccess(): bool
    {
        foreach (CheckRegistry::getChecks() as $check) {
            $checkClass = $check['class'];
            if ($checkClass instanceof AbstractCheck) {
                $checkClass->setToken($this->token);
                if ($checkClass->hasAccess() === false) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Checks whether the current request is authenticated as a TYPO3 backend user.
     *
     * @return bool True, when a backend user is logged in, false if not
     *
     * @throws AspectNotFoundException
     * @throws AspectPropertyNotFoundException
     */
    protected function isBackendUser(): bool
    {
        $backendUser = $this->context->getAspect('backend.user');

        return $backendUser->get('id') !== 0;
    }

    /**
     * Guesses the mime type from the file extension when it could not be determined otherwise.
     *
     * @param string $file The absolute path to the file
     *
     * @return false|string The guessed mime type, or false when the extension is not known
     */
    protected function guessMimeTypeByFileExtension(string $file): false|string
    {
        $lowercaseFileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (isset(MimeTypes::ADDITIONAL_MIME_TYPES[$lowercaseFileExtension])) {
            return MimeTypes::ADDITIONAL_MIME_TYPES[$lowercaseFileExtension];
        }
        return false;
    }

    /**
     * Checks whether the file should be forced to download.
     *
     * @param string $fileExtension The extension of the file
     *
     * @return bool True if the download of given file type should be forced, false if not.
     */
    protected function shouldForceDownload(string $fileExtension): bool
    {
        $forceDownloadTypes = $this->extensionConfiguration->getForceDownloadTypes();

        if ($this->extensionConfiguration->isForceDownload() && ($forceDownloadTypes !== '' && $forceDownloadTypes !== '0')) {
            if ($forceDownloadTypes === ExtensionConfiguration::FILE_TYPES_WILDCARD) {
                return true;
            }

            $forceDownloadPattern = sprintf('/^(%s)$/i', $this->extensionConfiguration->getForceDownloadTypes());

            return (bool)preg_match($forceDownloadPattern, $fileExtension);
        }

        return false;
    }

    /**
     * Dispatches the OutputInitializationEvent.
     */
    protected function dispatchOutputInitializationEvent(): void
    {
        $event = new OutputInitializationEvent($this->token);
        $event = $this->eventDispatcher->dispatch($event);
        $this->token = $event->getToken();
    }

    /**
     * Dispatches the AfterFileRetrievedEvent.
     *
     * @param string $file     Contains the absolute path to the file on the file system. You can change this property.
     * @param string $fileName Contains the name of the file. You can change this so that another file name is used when
     *                         downloading this file.
     */
    protected function dispatchAfterFileRetrievedEvent(string &$file, string &$fileName): void
    {
        $event = new AfterFileRetrievedEvent($file, $fileName);
        $event = $this->eventDispatcher->dispatch($event);
        $file = $event->getFile();
        $fileName = $event->getFileName();
    }

    /**
     * Dispatches the BeforeReadDeliverEvent.
     *
     * @param string[]  $header         An array of header which will be sent to the browser. You can add your own headers or remove
     *                               default ones.
     * @param string $fileName       The name of the file. This property is read-only.
     * @param string $mimeType       The mime type of the file. This property is read-only.
     * @param bool   $forceDownload  Information whether the file should be forced to download or not. This property is read-only.
     */
    protected function dispatchBeforeReadDeliverEvent(
        array &$header,
        string $fileName,
        string $mimeType,
        bool $forceDownload
    ): void {
        $event = new BeforeReadDeliverEvent($header, $fileName, $mimeType, $forceDownload);
        $event = $this->eventDispatcher->dispatch($event);
        $header = $event->getHeader();
    }
}
