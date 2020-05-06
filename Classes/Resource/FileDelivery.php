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
use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Bitmotion\SecureDownloads\Resource\Event\AfterFileRetrievedEvent;
use Bitmotion\SecureDownloads\Resource\Event\BeforeReadDeliverEvent;
use Bitmotion\SecureDownloads\Resource\Event\OutputInitializationEvent;
use Bitmotion\SecureDownloads\Utility\HookUtility;
use Bitmotion\SecureDownloads\Utility\MimeTypeUtility;
use Firebase\JWT\JWT;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

/**
 * ToDo: Use PSR-7 HTTP message instead.
 */
class FileDelivery
{
    /**
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

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
     */
    protected $userId;

    /**
     * @var int
     */
    protected $pageId;

    /**
     * @var string
     */
    protected $userGroups;

    /**
     * @var int
     */
    protected $expiryTime;

    /**
     * @var string
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
     * FileDelivery constructor.
     *
     * Check the access rights
     */
    public function __construct(string $jwt)
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);

        $this->getDataFromJsonWebToken($jwt);
        $this->dispatchOutputInitializationEvent();
        $this->userAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');

        if (!$this->checkUserAccess() || !$this->checkGroupAccess()) {
            $this->exitScript('Access denied for User!');
        }
    }

    /**
     * Get data from cache if JWT was decoded before. If not, decode given JWT.
     */
    protected function getDataFromJsonWebToken(string $jwt): void
    {
        if (DecodeCache::hasCache($jwt)) {
            $data = DecodeCache::getCache($jwt);
        } else {
            try {
                $data = JWT::decode($jwt, $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'], ['HS256']);
                DecodeCache::addCache($jwt, $data);
            } catch (\Exception $exception) {
                $this->exitScript($exception->getMessage());
            }
        }

        // Hook for doing stuff with JWT data
        // This is deprecated as there will be a dedicated class for handling JWTs.
        HookUtility::executeHook('output', 'encode', $data, $this);

        $this->userGroups = implode(',', $data->groups);
        $this->userId = $data->user;
        $this->pageId = $data->page;
        $this->expiryTime = $data->exp;
        $this->file = $data->file;
    }

    protected function exitScript(string $message, $httpStatus = HttpUtility::HTTP_STATUS_403): void
    {
        // TODO: Log message?
        HttpUtility::setResponseCodeAndExit($httpStatus);
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
            return $this->userId === $this->userAspect->get('id');
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

        if (!empty($groupCheckDirs) && !preg_match('/' . $this->softQuoteExpression($groupCheckDirs) . '/', $this->file)) {
            return false;
        }

        $actualGroups = $this->userAspect->get('groupIds');
        sort($actualGroups);
        $transmittedGroups = GeneralUtility::intExplode(',', $this->userGroups);
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

    /**
     * Output the requested file
     */
    public function deliver(): void
    {
        $file = GeneralUtility::getFileAbsFileName(ltrim($this->file, '/'));
        $fileName = basename($file);

        // This is a workaround for a PHP bug on Windows systems:
        // @see http://bugs.php.net/bug.php?id=46990
        // It helps for filenames with special characters that are present in latin1 encoding.
        // If you have real UTF-8 filenames, use a nix based OS.
        if (Environment::isWindows()) {
            $file = utf8_decode($file);
        }

        $this->dispatchAfterFileRetrievedEvent($file, $fileName);

        if (file_exists($file)) {
            $this->fileSize = filesize($file);
            $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
            $forceDownload = $this->shouldForceDownload($fileExtension);
            $mimeType = MimeTypeUtility::getMimeType($file) ?? 'application/octet-stream';
            $header = $this->getHeader($mimeType, $fileName, $forceDownload);
            $outputFunction = $this->extensionConfiguration->getOutputFunction();

            $this->dispatchBeforeFileDeliverEvent($outputFunction, $header, $fileName, $mimeType, $forceDownload);

            if ($this->isProcessed === false && $this->extensionConfiguration->isLog()) {
                $this->logDownload($this->fileSize, $mimeType);
            }

            $this->sendHeader($header);
            $this->outputFile($outputFunction, $file);
            exit;
        }

        $this->exitScript('File does not exist!', HttpUtility::HTTP_STATUS_404);
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

    protected function sendHeader(array $header): void
    {
        foreach ($header as $name => $value) {
            header(sprintf('%s: %s', $name, $value));
        }
    }

    protected function outputFile(string $outputFunction, string $file): void
    {
        switch ($outputFunction) {
            case ExtensionConfiguration::OUTPUT_STREAM:
                $this->streamFile($file);
                break;

            case ExtensionConfiguration::OUTPUT_PASS_THRU:
                $this->passThruFile($file);
                break;

            case ExtensionConfiguration::OUTPUT_NGINX:
                $this->nginxDeliverFile($file);
                break;

            case ExtensionConfiguration::OUTPUT_READ_FILE:
            default:
                readfile($file);
        }

        // make sure we can detect an aborted connection, call flush
        ob_flush();
        flush();
    }

    /**
     * Log the access of the file
     */
    protected function logDownload(int $fileSize = 0, string $mimeType = ''): void
    {
        $log = new Log();
        $log->setFileSize($this->fileSize ?? $fileSize);

        $pathInfo = pathinfo($this->file);
        $log->setFilePath($pathInfo['dirname'] . '/' . $pathInfo['filename']);
        $log->setFileType($pathInfo['extension']);
        $log->setFileName($pathInfo['filename']);
        $log->setMediaType($mimeType);

        if ($fileObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->file)) {
            $log->setFileId((string)$fileObject->getUid());
        }

        $log->setUser($this->userAspect->get('id'));
        $log->setPage($this->pageId);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_securedownloads_domain_model_log');
        $queryBuilder->insert('tx_securedownloads_domain_model_log')->values($log->toArray())->execute();

        $this->isProcessed = true;
    }

    // File delivery methods

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

    protected function passThruFile(string $fileName): void
    {
        $handle = fopen($fileName, 'rb');
        fpassthru($handle);
        fclose($handle);
    }

    protected function nginxDeliverFile(string $fileName): void
    {
        if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'nginx') === 0) {
            $this->sendHeader([
                'X-Accel-Redirect' => sprintf(
                    '%s/%s',
                    rtrim($this->extensionConfiguration->getProtectedPath(), '/'),
                    $this->file
                ),
            ]);
        } else {
            readfile($fileName);
        }
    }

    // Event handling

    protected function initializeEventDispatcher(): EventDispatcherInterface
    {
        $this->eventDispatcher = GeneralUtility::getContainer()->get(EventDispatcherInterface::class);

        return $this->eventDispatcher;
    }

    protected function dispatchOutputInitializationEvent()
    {
        $event = new OutputInitializationEvent($this->userId, $this->userGroups, $this->file, $this->expiryTime);
        $event = ($this->eventDispatcher ?? $this->initializeEventDispatcher())->dispatch($event);
        $this->userId = $event->getUserId();
        $this->userGroups = $event->getUserGroups();
        $this->file = $event->getFile();
        $this->expiryTime = $event->getExpiryTime();
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
}
