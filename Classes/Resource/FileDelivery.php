<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Resource;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Firebase\JWT\JWT;
use Leuchtfeuer\SecureDownloads\Cache\DecodeCache;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\AbstractToken;
use Leuchtfeuer\SecureDownloads\Registry\CheckRegistry;
use Leuchtfeuer\SecureDownloads\Registry\TokenRegistry;
use Leuchtfeuer\SecureDownloads\Resource\Event\AfterFileRetrievedEvent;
use Leuchtfeuer\SecureDownloads\Resource\Event\BeforeReadDeliverEvent;
use Leuchtfeuer\SecureDownloads\Resource\Event\OutputInitializationEvent;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\ErrorController;
use TYPO3\CMS\Frontend\Page\PageAccessFailureReasons;

class FileDelivery implements SingletonInterface
{
    /**
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

    /**
     * @var AbstractToken
     */
    protected $token;

    /**
     * @var int
     */
    protected $fileSize;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var array
     */
    protected $header = [];

    public function __construct(ExtensionConfiguration $extensionConfiguration, EventDispatcher $eventDispatcher)
    {
        $this->extensionConfiguration = $extensionConfiguration;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param string $jsonWebToken
     * @return ResponseInterface
     */
    public function deliver(string $jsonWebToken, ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->retrieveDataFromJsonWebToken($jsonWebToken)) {
            return $this->getAccessDeniedResponse($request, 'Could not parse token.');
        }

        $this->dispatchOutputInitializationEvent();

        if (!$this->hasAccess()) {
            return $this->getAccessDeniedResponse($request, 'Access check failed.');
        }

        $file = GeneralUtility::getFileAbsFileName(ltrim($this->token->getFile(), '/'));
        $fileName = basename($file);

        if (Environment::isWindows()) {
            $file = utf8_decode($file);
        }

        $this->dispatchAfterFileRetrievedEvent($file, $fileName);

        if (file_exists($file)) {
            return new Response($this->getResponseBody($file, $fileName), 200, $this->header, '');
        }

        return $this->getFileNotFoundResponse($request, 'File does not exist!');
    }

    /**
     * Get data from cache if JWT was decoded before. If not, decode given JWT.
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
            } catch (\Exception $exception) {
                return false;
            }
        }

        return true;
    }

    protected function getAccessDeniedResponse(ServerRequestInterface $request, string $reason): ResponseInterface
    {
        return GeneralUtility::makeInstance(ErrorController::class)->accessDeniedAction(
            $request,
            'Access denied!',
            [$reason]
        );
    }

    protected function getFileNotFoundResponse(ServerRequestInterface $request, string $reason): ResponseInterface
    {
        return GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction(
            $request,
            $reason,
            ['code' => PageAccessFailureReasons::PAGE_NOT_FOUND]
        );
    }

    /**
     * @return bool
     */
    protected function hasAccess(): bool
    {
        foreach (CheckRegistry::getChecks() ?? [] as $check) {
            $check['class']->setToken($this->token);

            if ($check['class']->hasAccess() === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $file
     * @param string $fileName
     * @return StreamInterface|string
     */
    protected function getResponseBody(string $file, string $fileName)
    {
        $this->fileSize = filesize($file);
        $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
        $forceDownload = $this->shouldForceDownload($fileExtension);
        $mimeType = (new FileInfo($file))->getMimeType() ?? 'application/octet-stream';
        $this->header = $this->getHeader($mimeType, $fileName, $forceDownload);
        $outputFunction = $this->extensionConfiguration->getOutputFunction();

        $this->dispatchBeforeFileDeliverEvent($outputFunction, $this->header, $fileName, $mimeType, $forceDownload);

        if ($this->extensionConfiguration->isLog()) {
            $this->token->log([
                'fileSize' => $this->fileSize,
                'mimeType' => $mimeType,
            ]);
        }

        return $this->outputFile($outputFunction, $file) ?? 'php://temp';
    }

    /**
     * @param string $fileExtension
     * @return bool
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
     * @param string $mimeType
     * @param string $fileName
     * @param bool $forceDownload
     * @return string[]
     */
    protected function getHeader(string $mimeType, string $fileName, bool $forceDownload): array
    {
        $header = [
            'Pragma' => 'private',
            'Expires' => '0', // set expiration time
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Content-Type' => $mimeType,
        ];

        if (!@ini_get('zlib.output_compression')) {
            $header['Content-Length'] = $this->fileSize;
        }

        if ($forceDownload === true) {
            $header['Content-Disposition'] = sprintf('attachment; filename="%s"', $fileName);
        }

        return $header;
    }

    /**
     * @param string $outputFunction
     * @param string $file
     * @return StreamInterface|null
     */
    protected function outputFile(string $outputFunction, string $file): ?StreamInterface
    {
        if ($outputFunction === ExtensionConfiguration::OUTPUT_NGINX) {
            if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'nginx') === 0) {
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

    protected function dispatchOutputInitializationEvent()
    {
        $event = new OutputInitializationEvent($this->token);
        $event = $this->eventDispatcher->dispatch($event);
        $this->token = $event->getToken();
    }

    protected function dispatchBeforeFileDeliverEvent(&$outputFunction, &$header, $fileName, $mimeType, $forceDownload)
    {
        $event = new BeforeReadDeliverEvent($outputFunction, $header, $fileName, $mimeType, $forceDownload);
        $event = $this->eventDispatcher->dispatch($event);
        $outputFunction = $event->getOutputFunction();
        $header = $event->getHeader();
    }

    protected function dispatchAfterFileRetrievedEvent(string &$file, string &$fileName)
    {
        $event = new AfterFileRetrievedEvent($file, $fileName);
        $event = $this->eventDispatcher->dispatch($event);
        $file = $event->getFile();
        $fileName = $event->getFileName();
    }
}
