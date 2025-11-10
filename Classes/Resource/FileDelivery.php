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
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Error\Http\PageNotFoundException;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

class FileDelivery implements SingletonInterface
{
    protected AbstractToken $token;

    /**
     * @var string[]
     */
    protected array $header = [];

    public function __construct(
        protected ExtensionConfiguration $extensionConfiguration,
        protected EventDispatcherInterface $eventDispatcher,
        protected ResourceFactory $resourceFactory
    ) {}

    /**
     * Delivers the file to the browser if all checks pass and file exists.
     *
     * @param string                 $jsonWebToken The JSON Web token given in the URL
     * @param ServerRequestInterface $request      The server request
     *
     * @return ResponseInterface Either the valid file as a stream or an error response
     *
     * @throws PageNotFoundException|ResourceDoesNotExistException
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

        $file = GeneralUtility::getFileAbsFileName(ltrim($this->token->getFile(), '/'));
        $fileName = basename($file);

        if (Environment::isWindows()) {
            $file = utf8_decode($file);
        }

        $this->dispatchAfterFileRetrievedEvent($file, $fileName);

        if (file_exists($file)) {
            $fileObject = $this->resourceFactory->retrieveFileOrFolderObject($file);

            if ($this->extensionConfiguration->isLog()) {
                $this->token->log([
                    'fileSize' => $fileSize = (int)filesize($file),
                    'mimeType' => (new FileInfo($file))->getMimeType()
                        ?: $this->guessMimeTypeByFileExtension($file)
                            ?: MimeTypes::DEFAULT_MIME_TYPE,
                ]);
            }

            if ($fileObject instanceof File) {
                $response = $fileObject
                    ->getStorage()
                    ->streamFile(
                        $fileObject,
                        $this->shouldForceDownload($fileObject->getExtension()),
                        $fileName
                    );
                ob_end_clean();

                return $response;
            }

            return new Response(
                $this->getResponseBody($file, $fileName),
                200,
                $this->header,
                ''
            );
        }

        return $this->getFileNotFoundResponse($request, 'File does not exist!');
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

    protected function isBackendUser(): bool
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $backendUser = $context->getAspect('backend.user');

        return $backendUser->get('id') !== 0;
    }

    /**
     * Returns the response body. This method also dispatches the BeforeFileDeliverEvent.
     *
     * @param string $file     The actual absolute path to the file
     * @param string $fileName The name of the file
     *
     * @return StreamInterface|string Whether a stream or a string, when x-accel-redirect is used
     */
    protected function getResponseBody(string $file, string $fileName): StreamInterface|string
    {
        $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
        $forceDownload = $this->shouldForceDownload($fileExtension);
        $fileSize = (int)filesize($file);
        // Try to get MimeType via TYPO3 buildin logic first. If that fails, use our extended file extension list.
        $mimeType = (new FileInfo($file))->getMimeType() ?: $this->guessMimeTypeByFileExtension($file) ?: MimeTypes::DEFAULT_MIME_TYPE;
        $outputFunction = $this->extensionConfiguration->getOutputFunction();
        $header = $this->getFileHeader($mimeType, $fileName, $forceDownload, $fileSize);

        $this->dispatchBeforeFileDeliverEvent($outputFunction, $header, $fileName, $mimeType, $forceDownload);
        $this->header = $header;

        return $this->outputFile($outputFunction, $file) ?? 'php://temp';
    }

    protected function guessMimeTypeByFileExtension(string $file): false|string
    {
        $lowercaseFileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!empty(MimeTypes::ADDITIONAL_MIME_TYPES[$lowercaseFileExtension])) {
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

        if ($this->extensionConfiguration->isForceDownload() && !empty($forceDownloadTypes)) {
            if ($forceDownloadTypes === ExtensionConfiguration::FILE_TYPES_WILDCARD) {
                return true;
            }

            $forceDownloadPattern = sprintf('/^(%s)$/i', $this->extensionConfiguration->getForceDownloadTypes());

            return (bool)preg_match($forceDownloadPattern, $fileExtension);
        }

        return false;
    }

    /**
     * Sets default HTTP headers which can be modified in the BeforeFileDeliver event.
     *
     * @param string $mimeType       The mime type of the file
     * @param string $fileName       The name of the file
     * @param bool   $forceDownload  Whether the file should be forced to download
     * @param int    $fileSize       The actual file size
     *
     * @return string[] An array of HTTP header
     */
    protected function getFileHeader(string $mimeType, string $fileName, bool $forceDownload, int $fileSize): array
    {
        $header = [
            'Pragma' => 'private',
            'Expires' => '0',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Type' => $mimeType,
        ];

        if (!@ini_get('zlib.output_compression')) {
            $header['Content-Length'] = (string)$fileSize;
        }

        if ($forceDownload) {
            $header['Content-Disposition'] = sprintf('attachment; filename="%s"', $fileName);
        }

        return $header;
    }

    /**
     * Checks whether the file should be delivered via x-accel-redirect header or as stream.
     *
     * @param string $outputFunction The method how the file should be delivered to the user
     * @param string $file           The absolute file path
     *
     * @return StreamInterface|null  The content stream or null if x-accel-redirect is used
     */
    protected function outputFile(string $outputFunction, string $file): ?StreamInterface
    {
        if ($outputFunction === ExtensionConfiguration::OUTPUT_NGINX) {
            if (isset($_SERVER['SERVER_SOFTWARE']) && str_starts_with((string)$_SERVER['SERVER_SOFTWARE'], 'nginx')) {
                $this->header['X-Accel-Redirect'] = sprintf(
                    '%s/%s',
                    rtrim($this->extensionConfiguration->getProtectedPath(), '/'),
                    $file
                );

                return null;
            }
        }

        return new Stream($file);
    }

    // Event handling

    /**
     * Dispatches the OutputInitializationEvent event.
     */
    protected function dispatchOutputInitializationEvent(): void
    {
        $event = new OutputInitializationEvent($this->token);
        $event = $this->eventDispatcher->dispatch($event);
        $this->token = $event->getToken();
    }

    /**
     * Dispatches the AfterFileRetrieved event.
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
     * Dispatches the BeforeFileDeliver event.
     *
     * @param string $outputFunction Contains the output function as string. This property is deprecated and will be removed in
     *                               further releases since the output function can only be one of "x-accel-redirect" or "stream".
     * @param string[]  $header         An array of header which will be sent to the browser. You can add your own headers or remove
     *                               default ones.
     * @param string $fileName       The name of the file. This property is read-only.
     * @param string $mimeType       The mime type of the file. This property is read-only.
     * @param bool   $forceDownload  Information whether the file should be forced to download or not. This property is read-only.
     */
    protected function dispatchBeforeFileDeliverEvent(
        string &$outputFunction,
        array &$header,
        string $fileName,
        string $mimeType,
        bool $forceDownload
    ): void {
        $event = new BeforeReadDeliverEvent($outputFunction, $header, $fileName, $mimeType, $forceDownload);
        $event = $this->eventDispatcher->dispatch($event);
        $outputFunction = $event->getOutputFunction();
        $header = $event->getHeader();
    }
}
