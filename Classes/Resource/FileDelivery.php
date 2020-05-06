<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Resource;

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

use Bitmotion\SecureDownloads\Cache\DecodeCache;
use Bitmotion\SecureDownloads\Domain\Model\Log;
use Bitmotion\SecureDownloads\Domain\Transfer\Download;
use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Bitmotion\SecureDownloads\Resource\Event\AfterFileRetrievedEvent;
use Bitmotion\SecureDownloads\Resource\Event\BeforeReadDeliverEvent;
use Bitmotion\SecureDownloads\Resource\Event\OutputInitializationEvent;
use Bitmotion\SecureDownloads\Utility\MimeTypeUtility;
use Firebase\JWT\JWT;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

class FileDelivery implements SingletonInterface
{
    /**
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

    /**
     * @var Download
     */
    protected $download;

    /**
     * @var UserAspect
     */
    protected $userAspect;

    /**
     * @var int
     */
    protected $fileSize;

    /**
     * @var int
     * @deprecated Will be removed with version 6.
     */
    protected $userId;

    /**
     * @var int
     * @deprecated Will be removed with version 6.
     */
    protected $pageId;

    /**
     * @var string
     * @deprecated Will be removed with version 6.
     */
    protected $userGroups;

    /**
     * @var int
     * @deprecated Will be removed with version 6.
     */
    protected $expiryTime;

    /**
     * @var string
     * @deprecated Will be removed with version 6.
     */
    protected $file;

    /**
     * @var bool
     */
    protected $isProcessed = false;

    /**
     * @var \Psr\EventDispatcher\EventDispatcherInterface|null
     */
    protected $eventDispatcher;

    /**
     * @var array
     */
    protected $header = [];

    /**
     * FileDelivery constructor.
     *
     * Check the access rights
     */
    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    /**
     * Output the requested file
     */
    public function deliver(string $jwt): ResponseInterface
    {
        if (!$this->retrieveDataFromJsonWebToken($jwt)) {
            return new Response('php://temp', 403);
        }

        $this->dispatchOutputInitializationEvent();

        if (!$this->hasAccess()) {
            return new Response('php://temp', 403);
        }

        $file = GeneralUtility::getFileAbsFileName(ltrim($this->download->getFile(), '/'));
        $fileName = basename($file);

        if (Environment::isWindows()) {
            $file = utf8_decode($file);
        }

        $this->dispatchAfterFileRetrievedEvent($file, $fileName);

        if (file_exists($file)) {
            $this->fileSize = filesize($file);
            $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
            $forceDownload = $this->shouldForceDownload($fileExtension);
            $mimeType = MimeTypeUtility::getMimeType($file) ?? 'application/octet-stream';
            $this->header = $this->getHeader($mimeType, $fileName, $forceDownload);
            $outputFunction = $this->extensionConfiguration->getOutputFunction();

            $this->dispatchBeforeFileDeliverEvent($outputFunction, $this->header, $fileName, $mimeType, $forceDownload);

            if ($this->extensionConfiguration->isLog()) {
                $this->download->log($this->fileSize, $mimeType, $this->userAspect->get('id'));
            }

            $body = $this->outputFile($outputFunction, $file) ?? 'php://temp';
            return new Response($body, 200, $this->header, '');
        }

        return new Response((new Stream('File does not exist!', 'rw')), 404);
    }

    /**
     * Get data from cache if JWT was decoded before. If not, decode given JWT.
     */
    protected function retrieveDataFromJsonWebToken(string $jwt): bool
    {
        if (DecodeCache::hasCache($jwt)) {
            $this->download = DecodeCache::getCache($jwt);
        } else {
            try {
                $this->download = new Download($jwt);
                DecodeCache::addCache($jwt, $this->dowload);
            } catch (\Exception $exception) {
                return false;
            }
        }

        return true;
    }

    protected function hasAccess(): bool
    {
        $this->userAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');

        if (!$this->checkUserAccess() || !$this->checkGroupAccess()) {
            return false;
        }

        return true;
    }

    /**
     * Returns TRUE when the user has direct access to the file or group check is enabled
     * Returns FALSE if the user has noch direct access to the file and group check is disabled
     *
     * @return bool
     */
    protected function checkUserAccess(): bool
    {
        if ($this->extensionConfiguration->isEnableGroupCheck() || $this->userId === 0) {
            return true;
        }

        try {
            return $this->download->getUser() === $this->userAspect->get('id');
        } catch (AspectPropertyNotFoundException $exception) {
            return false;
        }
    }

    /**
     * Returns true if the transmitted group list is identical
     * to the group list of the current user or both have at least one group
     * in common.
     */
    protected function checkGroupAccess(): bool
    {
        if (!$this->extensionConfiguration->isEnableGroupCheck()) {
            return true;
        }

        $groupCheckDirs = $this->extensionConfiguration->getGroupCheckDirs();

        if (!empty($groupCheckDirs) && !preg_match('/' . $this->softQuoteExpression($groupCheckDirs) . '/', $this->download->getFile())) {
            return false;
        }

        $actualGroups = $this->userAspect->get('groupIds');
        sort($actualGroups);
        $transmittedGroups = $this->download->getGroups();
        sort($transmittedGroups);

        if ($actualGroups === $transmittedGroups) {
            // Actual groups and transmitted groups are identically, so we can ignore the excluded groups
            return true;
        }

        if ($this->extensionConfiguration->isStrictGroupCheck()) {
            // Groups are not identically. Deny access when strict group access is enabled.
            return false;
        }

        $excludedGroups = GeneralUtility::intExplode(',', $this->extensionConfiguration->getExcludeGroups(), true);
        $verifiableGroups = array_diff($actualGroups, $excludedGroups);

        foreach ($verifiableGroups as $actualGroup) {
            if (in_array($actualGroup, $transmittedGroups, true)) {
                return true;
            }
        }

        return false;
    }

    protected function softQuoteExpression(string $string): string
    {
        $string = str_replace('\\', '\\\\', $string);
        $string = str_replace(' ', '\ ', $string);
        $string = str_replace('/', '\/', $string);
        $string = str_replace('.', '\.', $string);
        $string = str_replace(':', '\:', $string);

        return $string;
    }

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

    protected function initializeEventDispatcher(): EventDispatcherInterface
    {
        $this->eventDispatcher = GeneralUtility::getContainer()->get(EventDispatcherInterface::class);

        return $this->eventDispatcher;
    }

    protected function dispatchOutputInitializationEvent()
    {
        $event = new OutputInitializationEvent($this->download);
        $event = ($this->eventDispatcher ?? $this->initializeEventDispatcher())->dispatch($event);
        $this->download = $event->getDownload();
    }

    protected function dispatchBeforeFileDeliverEvent(&$outputFunction, &$header, $fileName, $mimeType, $forceDownload)
    {
        $event = new BeforeReadDeliverEvent($outputFunction, $header, $fileName, $mimeType, $forceDownload);
        $event = ($this->eventDispatcher ?? $this->initializeEventDispatcher())->dispatch($event);
        $outputFunction = $event->getOutputFunction();
        $header = $event->getHeader();
    }

    protected function dispatchAfterFileRetrievedEvent(string &$file, string &$fileName)
    {
        $event = new AfterFileRetrievedEvent($file, $fileName);
        $event = ($this->eventDispatcher ?? $this->initializeEventDispatcher())->dispatch($event);
        $file = $event->getFile();
        $fileName = $event->getFileName();
    }

    // Deprecated

    /**
     * @deprecated Will be removed with version 6.
     */
    protected function exitScript(string $message, $httpStatus = HttpUtility::HTTP_STATUS_403): void
    {
        // TODO: Log message?
        HttpUtility::setResponseCodeAndExit($httpStatus);
    }

    /**
     * @deprecated Will be removed with version 6.
     */
    protected function streamFile(string $fileName): void
    {
        $stream = new Stream($fileName);
        $stream->rewind();

        while (!$stream->eof()) {
            echo $stream->read(4096);
            ob_flush();
            flush();
        }

        $stream->close();
    }

    /**
     * @deprecated Will be removed with version 6.
     */
    protected function passThruFile(string $fileName): void
    {
        $handle = fopen($fileName, 'rb');
        fpassthru($handle);
        fclose($handle);
    }

    /**
     * @deprecated Will be removed with version 6.
     */
    protected function nginxDeliverFile(string $fileName): void
    {
        if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'nginx') === 0) {
            $this->header['X-Accel-Redirect'] = sprintf(
                '%s/%s',
                rtrim($this->extensionConfiguration->getProtectedPath(), '/'),
                $this->file
            );
        } else {
            $this->streamFile($fileName);
        }
    }

    /**
     * @deprecated Will be removed with version 6.
     */
    protected function sendHeader(array $header): void
    {
        foreach ($header as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }
    }

    /**
     * @deprecated Will be removed with version 6.
     */
    protected function getDataFromJsonWebToken(string $jwt): void
    {
        $this->retrieveDataFromJsonWebToken($jwt);
    }

    /**
     * Log the access of the file
     * @deprecated Will be removed with version 6.
     */
    protected function logDownload(int $fileSize = 0, string $mimeType = ''): void
    {
        $log = new Log();
        $log->setFileSize($this->fileSize ?? $fileSize);

        $pathInfo = pathinfo($this->download->getFile());
        $log->setFilePath($pathInfo['dirname'] . '/' . $pathInfo['filename']);
        $log->setFileType($pathInfo['extension']);
        $log->setFileName($pathInfo['filename']);
        $log->setMediaType($mimeType);

        if ($fileObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->download->getFile())) {
            $log->setFileId((string)$fileObject->getUid());
        }

        $log->setUser($this->userAspect->get('id'));
        $log->setPage($this->download->getPage());

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_securedownloads_domain_model_log');
        $queryBuilder->insert('tx_securedownloads_domain_model_log')->values($log->toArray())->execute();

        $this->isProcessed = true;
    }
}
