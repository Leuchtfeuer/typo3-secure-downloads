<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\Resource;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

use Bitmotion\SecureDownloads\Cache\DecodeCache;
use Bitmotion\SecureDownloads\Domain\Model\Log;
use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Bitmotion\SecureDownloads\Parser\HtmlParser;
use Bitmotion\SecureDownloads\Request\RequestContext;
use Bitmotion\SecureDownloads\Utility\HookUtility;
use Bitmotion\SecureDownloads\Utility\MimeTypeUtility;
use Firebase\JWT\JWT;
use Firebase\JWT\SignatureInvalidException;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;

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
     * @deprecated Will be removed in version 5.
     */
    protected $hash = '';

    /**
     * @var string
     */
    protected $file;

    /**
     * @var string
     * @deprecated Will be removed in version 5.
     */
    protected $calculatedHash = '';

    /**
     * @var bool
     */
    protected $isProcessed = false;

    /**
     * FileDelivery constructor.
     *
     * Check the access rights
     */
    public function __construct(?string $jwt = null)
    {
        $this->extensionConfiguration = new ExtensionConfiguration();

        if ($jwt !== null) {
            $this->getDataFromJsonWebToken($jwt);
        } else {
            // TODO: This part is deprecated and will be removed with version 5.
            $this->userGroups = (!empty(GeneralUtility::_GET('g'))) ? GeneralUtility::_GET('g') : '0';
            $this->hash = GeneralUtility::_GP('hash');
            $this->userId = (int)GeneralUtility::_GP('u');
            $this->pageId = (int)GeneralUtility::_GP('p');
            $this->expiryTime = (int)GeneralUtility::_GP('t');
            $this->file = GeneralUtility::_GP('file');
            $this->calculatedHash = isset($data) ? '' : $this->getHash($this->file, $this->userId, $this->userGroups, $this->expiryTime);
        }

        // Hook for init:
        // TODO: The params array is deprecated as all information is given in the ref param of the hook.
        // TODO: Remove the params with version 5.
        $params = [
            'pObj' => $this,
            'userId' => &$this->userId,
            'userGroups' => &$this->userGroups,
            'file' => &$this->file,
            'expiryTime' => &$this->expiryTime,
            'hash' => &$this->hash,
            'calculatedHash' => &$this->calculatedHash,
        ];
        HookUtility::executeHook('output', 'init', $params, $this);

        if (!$jwt) {
            // TODO: This part is deprecated and will be removed with version 5
            if (!$this->hashValid()) {
                $this->exitScript('Hash invalid! Access denied!');
            }

            if ($this->expiryTimeExceeded()) {
                $this->exitScript('Link Expired. Access denied!');
            }
        }

        $this->userAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');

        if (($this->userId !== 0) && !$this->checkUserAccess() && !$this->checkGroupAccess()) {
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
                $requestContext = GeneralUtility::makeInstance(RequestContext::class);
                $data = JWT::decode($jwt, $requestContext->getAdditionalSecret(), ['HS256']);
                DecodeCache::addCache($jwt, $data);
            } catch (SignatureInvalidException $exception) {
                $this->exitScript('Hash invalid! Access denied!');
            }
        }

        // Hook for doing stuff with JWT data
        HookUtility::executeHook('output', 'encode', $data, $this);

        $this->userGroups = implode($data->groups);
        $this->userId = $data->user;
        $this->pageId = $data->page;
        $this->expiryTime = $data->exp;
        $this->file = $data->file;
    }

    /**
     * @deprecated Will be removed with version 5.
     */
    protected function getHash(string $resourceUri, int $userId, string $userGroupIds, int $validityPeriod = 0): string
    {
        trigger_error('Method getHash() will be removed in version 5.', E_USER_DEPRECATED);

        if ($this->extensionConfiguration->isEnableGroupCheck()) {
            $hashString = $userId . $userGroupIds . $resourceUri . $validityPeriod;
        } else {
            $hashString = $userId . $resourceUri . $validityPeriod;
        }

        return GeneralUtility::hmac($hashString, 'bitmotion_securedownload');
    }

    /**
     * @deprecated Will be removed in version 5.
     */
    protected function hashValid(): bool
    {
        trigger_error('Method hashValid() will be removed in version 5.', E_USER_DEPRECATED);

        return $this->calculatedHash === $this->hash;
    }

    protected function exitScript(string $message): void
    {
        // TODO: Log message?
        HttpUtility::setResponseCodeAndExit(HttpUtility::HTTP_STATUS_403);
    }

    /**
     * @deprecated Will be removed in version 5.
     */
    protected function expiryTimeExceeded(): bool
    {
        trigger_error('Method expiryTimeExceeded() will be removed in version 5.', E_USER_DEPRECATED);

        return $this->expiryTime < time();
    }

    protected function checkUserAccess(): bool
    {
        return $this->userId === $this->userAspect->get('id');
    }

    /**
     * Returns true if the transmitted group list is identical
     * to the group list of the current user or both have at least one group
     * in common.
     */
    protected function checkGroupAccess(): bool
    {
        $accessAllowed = false;
        if (!$this->extensionConfiguration->isEnableGroupCheck()) {
            return false;
        }

        $groupCheckDirs = $this->extensionConfiguration->getGroupCheckDirs();

        if (!empty($groupCheckDirs) && !preg_match('/' . HtmlParser::softQuoteExpression($groupCheckDirs) . '/', $this->file)) {
            return false;
        }

        $transmittedGroups = GeneralUtility::intExplode(',', $this->userGroups);
        $actualGroups = $this->userAspect->get('groupIds');
        sort($actualGroups);
        $excludedGroups = GeneralUtility::intExplode(',', $this->extensionConfiguration->getExcludeGroups());
        $checkableGroups = array_diff($actualGroups, $excludedGroups);

        if ($actualGroups === $transmittedGroups) {
            return true;
        }

        // TODO: This loosens the permission check to an extend which might lead to unexpected file access.
        // We may need to remove it or at least make it configurable
        foreach ($checkableGroups as $actualGroup) {
            if (in_array($actualGroup, $transmittedGroups, true)) {
                $accessAllowed = true;
                break;
            }
        }

        return $accessAllowed;
    }

    /**
     * @deprecated Will be removed in version 5. Use HtmlParser::softQuoteExpression instead.
     */
    protected function softQuoteExpression(string $string): string
    {
        return HtmlParser::softQuoteExpression($string);
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

        // Hook for pre-output:
        // TODO: The pObj property of params array is deprecated as it is the same as the ref argument.
        // TODO: Remove the pObj property with version 5.
        $params = ['pObj' => &$this, 'file' => &$file, 'downloadName' => &$fileName];
        HookUtility::executeHook('output', 'preOutput', $params, $this);

        if (file_exists($file)) {
            $this->fileSize = filesize($file);
            $fileExtension = pathinfo($file, PATHINFO_EXTENSION);
            $forceDownload = $this->shouldForceDownload($fileExtension);
            $mimeType = MimeTypeUtility::getMimeType($file) ?? 'application/octet-stream';

            // Hook for output:
            // TODO: This hook is deprecated and will be removed with version 5. Use 'preReadFile' hook instead.
            // TODO: Remove the pObj property with version 5.
            $params = ['pObj' => &$this, 'fileExtension' => '.' . $fileExtension, 'mimeType' => &$mimeType];
            HookUtility::executeHook('output', 'output', $params, $this);

            $header = $this->getHeader($mimeType, $fileName, $forceDownload);
            $outputFunction = $this->extensionConfiguration->getOutputFunction();

            $params = ['outputFunction' => &$outputFunction, 'header' => &$header, 'fileName' => $fileName, 'mimeType' => $mimeType, 'forceDownload' => $forceDownload];
            HookUtility::executeHook('output', 'preReadFile', $params, $this);

            if ($this->isProcessed === false && $this->extensionConfiguration->isLog()) {
                $this->logDownload($this->fileSize, $mimeType);
            }

            $this->sendHeader($header);
            $this->outputFile($outputFunction, $file);
            exit;
        }
        print 'File does not exist!';
    }

    protected function shouldForceDownload(string $fileExtension, bool $forceDownload = false): bool
    {
        if ($this->extensionConfiguration->isForceDownload()) {
            $forceDownloadTypes = GeneralUtility::trimExplode('|', $this->extensionConfiguration->getForceDownloadTypes());

            // Handle the regex
            foreach ($forceDownloadTypes as &$forceDownloadType) {
                if (strpos($forceDownloadType, '?') !== false) {
                    $position = strpos($forceDownloadType, '?');
                    $start = $position - 1;
                    $end = $position + 1;
                    $forceDownloadTypes[] = substr($forceDownloadType, 0, $start) . substr($forceDownloadType, $end);
                    $forceDownloadType = str_replace('?', '', $forceDownloadType);
                }
            }
            unset($forceDownloadType);
            $forceDownload = $forceDownload || (is_array($forceDownloadTypes) && in_array($fileExtension, $forceDownloadTypes, true));
        }

        return $forceDownload;
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
            case 'readfile_chunked':
                $this->readFileFactional($file);
                break;

            case 'fpassthru':
                $handle = fopen($file, 'rb');
                fpassthru($handle);
                fclose($handle);
                break;

            case 'readfile':
                //fallthrough, this is the default case
            default:
                readfile($file);
                break;
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

        if ($fileObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->file)) {
            $log->setFilePath($fileObject->getPublicUrl());
            $log->setFileType($fileObject->getExtension());
            $log->setFileName($fileObject->getNameWithoutExtension());
            $log->setMediaType($fileObject->getMimeType());
            $log->setFileId((string)$fileObject->getUid());
        } else {
            $pathInfo = pathinfo($this->file);
            $log->setFilePath($pathInfo['dirname'] . '/' . $pathInfo['filename']);
            $log->setFileType($pathInfo['extension']);
            $log->setFileName($pathInfo['filename']);
            $log->setMediaType($mimeType);
        }

        $log->setUser($this->userAspect->get('id'));
        $log->setPage($this->pageId);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_securedownloads_domain_model_log');
        $queryBuilder->insert('tx_securedownloads_domain_model_log')->values($log->toArray())->execute();

        $this->isProcessed = true;
    }

    /**
     * In some cases php needs the filesize as php_memory, so big files cannot
     * be transferred. This function mitigates this problem.
     */
    protected function readFileFactional(string $fileName): bool
    {
        $outputChunkSize = $this->extensionConfiguration->getOutputChunkSize(); // how many bytes per chunk
        $timeout = ini_get('max_execution_time');
        $handle = fopen($fileName, 'rb');

        if ($handle === false) {
            return false;
        }

        while (!feof($handle) && (!connection_aborted())) {
            set_time_limit($timeout);
            $buffer = fread($handle, $outputChunkSize);
            print $buffer;
            ob_flush();
            flush();
        }

        return fclose($handle);
    }
}
