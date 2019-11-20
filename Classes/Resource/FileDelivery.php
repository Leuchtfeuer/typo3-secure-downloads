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

use Bitmotion\SecureDownloads\Domain\Model\Log;
use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Bitmotion\SecureDownloads\Parser\HtmlParser;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Utility\EidUtility;

class FileDelivery
{
    /**
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

    /**
     * @var FrontendUserAuthentication
     */
    protected $feUserObj;

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
    protected $hash;

    /**
     * @var string
     */
    protected $file;

    /**
     * @var string
     */
    protected $data;

    /**
     * @var string
     */
    protected $calculatedHash;

    /**
     * @var bool
     */
    protected $isProcessed = false;

    /**
     * FileDelivery constructor.
     *
     * Check the access rights
     */
    public function __construct()
    {
        $this->extensionConfiguration = new ExtensionConfiguration();

        $this->userId = (int)GeneralUtility::_GP('u') ?: 0;
        $this->pageId = (int)GeneralUtility::_GP('p') ?: 0;
        $this->userGroups = GeneralUtility::_GP('g');

        if ($this->userGroups === '') {
            $this->userGroups = 0;
        }

        $this->hash = GeneralUtility::_GP('hash');
        $this->expiryTime = (int) GeneralUtility::_GP('t');
        $this->file = GeneralUtility::_GP('file');

        $this->data = $this->userId . $this->userGroups . $this->file . $this->expiryTime;
        $this->calculatedHash = $this->getHash($this->file, $this->userId, $this->userGroups, $this->expiryTime);

        // Hook for init:
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['bitmotion']['secure_downloads']['output']['init'])) {
            $_params = [
                'pObj' => $this,
                'userId' => &$this->userId,
                'userGroups' => &$this->userGroups,
                'file' => &$this->file,
                'expiryTime' => &$this->expiryTime,
                'hash' => &$this->hash,
                'calculatedHash' => &$this->calculatedHash,
            ];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['bitmotion']['secure_downloads']['output']['init'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }

        if (!$this->hashValid()) {
            $this->exitScript('Hash invalid! Access denied!');
        }

        if ($this->expiryTimeExceeded()) {
            $this->exitScript('Link Expired. Access denied!');
        }

        $this->initializeUserAuthentication();

        if (($this->userId !== 0) && !$this->checkUserAccess() && !$this->checkGroupAccess()) {
            $this->exitScript('Access denied for User!');
        }
    }

    /**
     * TODO: Refactor it to a hash service
     */
    protected function getHash(string $resourceUri, int $userId, string $userGroupIds, int $validityPeriod = 0): string
    {
        if ($this->extensionConfiguration->isEnableGroupCheck()) {
            $hashString = $userId . $userGroupIds . $resourceUri . $validityPeriod;
        } else {
            $hashString = $userId . $resourceUri . $validityPeriod;
        }

        return GeneralUtility::hmac($hashString, 'bitmotion_securedownload');
    }

    protected function hashValid(): bool
    {
        return $this->calculatedHash === $this->hash;
    }

    protected function exitScript(string $message): void
    {
        header('HTTP/1.1 403 Forbidden');
        exit($message);
    }

    protected function expiryTimeExceeded(): bool
    {
        return $this->expiryTime < time();
    }

    protected function initializeUserAuthentication(): void
    {
        $this->feUserObj = EidUtility::initFeUser();
        $this->feUserObj->fetchGroupData();
    }

    protected function checkUserAccess(): bool
    {
        return $this->userId === (int)$this->feUserObj->user['uid'];
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

        if (!empty($groupCheckDirs) && !preg_match('/' . $this->softQuoteExpression($groupCheckDirs) . '/', $this->file)) {
            return false;
        }

        $transmittedGroups = GeneralUtility::intExplode(',', $this->userGroups);
        $actualGroups = array_unique(array_map('intval', $this->feUserObj->groupData['uid']));
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
        // FIXME: needs to be checked, if the website encoding really is UTF-8 and if UTF-8 filesystem is enabled
        if (TYPO3_OS === 'WIN') {
            $file = utf8_decode($file);
        }

        // Hook for pre-output:
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['bitmotion']['secure_downloads']['output']['preOutput'])) {
            $_params = ['pObj' => &$this, 'file' => &$file, 'downloadName' => &$fileName];
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['bitmotion']['secure_downloads']['output']['preOutput'] as $_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $_params, $this);
            }
        }

        if (file_exists($file)) {
            $this->fileSize = filesize($file);

            $this->logDownload();

            $strFileExtension = $this->getFileExtensionByFilename($file);

            $forcedownload = false;

            if ($this->extensionConfiguration->isForceDownload()) {
                $forcetypes = GeneralUtility::trimExplode('|', $this->extensionConfiguration->getForceDownloadTypes());

                // Handle the regex
                foreach ($forcetypes as &$forcetype) {
                    if (strpos($forcetype, '?') !== false) {
                        $position = strpos($forcetype, '?');
                        $start = $position - 1;
                        $end = $position + 1;
                        $forcetypes[] = substr($forcetype, 0, $start) . substr($forcetype, $end);
                        $forcetype = str_replace('?', '', $forcetype);
                    }
                }
                unset($forcetype);

                $forcedownload = $forcedownload || (is_array($forcetypes) && in_array($strFileExtension, $forcetypes, true));
            }

            $strMimeType = $this->getMimeTypeByFileExtension($strFileExtension);

            // Hook for output:
            // TODO: deprecate this hook?
            if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['bitmotion']['secure_downloads']['output']['output'])) {
                $_params = [
                    'pObj' => &$this,
                    'fileExtension' => '.' . $strFileExtension, // Add leading dot for compatibility in this hook
                    'mimeType' => &$strMimeType,
                ];
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['bitmotion']['secure_downloads']['output']['output'] as $_funcRef) {
                    GeneralUtility::callUserFunction($_funcRef, $_params, $this);
                }
            }

            //TODO: Check IE compatibility with these headers
            header('Pragma: private');
            header('Expires: 0'); // set expiration time
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header('Content-Type: ' . $strMimeType);

            $zlib_oc = @ini_get('zlib.output_compression');

            if (!$zlib_oc) {
                header('Content-Length: ' . $this->fileSize);
            }

            if ($forcedownload === true) {
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
            } else {
                header('Content-Disposition: inline; filename="' . $fileName . '"');
            }

            $strOutputFunction = trim($this->extensionConfiguration->getOutputFunction());
            switch ($strOutputFunction) {
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
            if ($strOutputFunction !== 'readfile_chunked' && !connection_aborted()) {
                $this->logDownload();
            }
        } else {
            print 'File does not exist!';
        }
    }

    /**
     * Log the access of the file
     */
    protected function logDownload(int $fileSize = 0): void
    {
        if ($this->isProcessed === false && $this->extensionConfiguration->isLog()) {
            $log = new Log();

            $log->setFileSize($fileSize ?: $this->fileSize);

            if ($fileObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->file)) {
                $log->setFilePath($fileObject->getPublicUrl());
                $log->setFileType($fileObject->getExtension());
                $log->setFileName($fileObject->getNameWithoutExtension());
                $log->setMediaType($fileObject->getMimeType());
                $log->setFileId((string)$fileObject->getUid());
            } else {
                $pathinfo = pathinfo($this->file);

                $log->setFilePath($pathinfo['dirname'] . '/' . $pathinfo['filename']);
                $log->setFileType($pathinfo['extension']);
                $log->setFileName($pathinfo['filename']);
                $log->setMediaType($this->getMimeTypeByFileExtension($pathinfo['extension']));
            }

            $log->setUser((int)$this->feUserObj->user['uid']);
            $log->setPage($this->pageId);

            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_securedownloads_domain_model_log');
            $queryBuilder
                ->insert('tx_securedownloads_domain_model_log')
                ->values($log->toArray())
                ->execute();

            $this->isProcessed = true;
        }
    }

    /**
     * Looks up the mime type for a give file extension
     * TODO: Use PHP functions instead - if available?
     *
     * @param string $strFileExtension lowercase file extension
     *
     * @return string mime type
     */
    protected function getMimeTypeByFileExtension(string $strFileExtension): string
    {
        // Check files with unknown file extensions, if they are image files (currently disabled)
        $checkForImageFiles = false;

        // Array with key/value pairs consisting of file extension (without dot in front) and mime type
        $arrMimeTypes = [
            // MS-Office filetypes
            'pps' => 'application/vnd.ms-powerpoint',
            'doc' => 'application/msword',
            'xls' => 'application/vnd.ms-excel',
            'docm' => 'application/vnd.ms-word.document.macroEnabled.12',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'dotm' => 'application/vnd.ms-word.template.macroEnabled.12',
            'dotx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.template',
            'ppsm' => 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12',
            'ppsx' => 'application/vnd.openxmlformats-officedocument.presentationml.slideshow',
            'pptm' => 'application/vnd.ms-powerpoint.presentation.macroEnabled.12',
            'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'xlsb' => 'application/vnd.ms-excel.sheet.binary.macroEnabled.12',
            'xlsm' => 'application/vnd.ms-excel.sheet.macroEnabled.12',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'xps' => 'application/vnd.ms-xpsdocument',

            // Open-Office filetypes
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ott' => 'application/vnd.oasis.opendocument.text-template',
            'odg' => 'application/vnd.oasis.opendocument.graphics',
            'otg' => 'application/vnd.oasis.opendocument.graphics-template',
            'odp' => 'application/vnd.oasis.opendocument.presentation',
            'otp' => 'application/vnd.oasis.opendocument.presentation-template',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
            'ots' => 'application/vnd.oasis.opendocument.spreadsheet-template',
            'odc' => 'application/vnd.oasis.opendocument.chart',
            'otc' => 'application/vnd.oasis.opendocument.chart-template',
            'odi' => 'application/vnd.oasis.opendocument.image',
            'oti' => 'application/vnd.oasis.opendocument.image-template',
            'odf' => 'application/vnd.oasis.opendocument.formula',
            'otf' => 'application/vnd.oasis.opendocument.formula-template',
            'odm' => 'application/vnd.oasis.opendocument.text-master',
            'oth' => 'application/vnd.oasis.opendocument.text-web',

            // Media file types
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'jpe' => 'image/jpeg',
            'gif' => 'image/gif',
            'png' => 'image/png',
            'mpeg' => 'video/mpeg',
            'mpg' => 'video/mpeg',
            'mpe' => 'video/mpeg',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'pdf' => 'application/pdf',
            'svg' => 'image/svg+xml',
            'flv' => 'video/x-flv',
            'swf' => 'application/x-shockwave-flash',
            'htm' => 'text/html',
            'html' => 'text/html',
        ];

        // Read all additional MIME types from the EM configuration into the array $strAdditionalMimeTypesArray
        if ($this->extensionConfiguration->getAdditionalMimeTypes()) {
            $strAdditionalFileExtension = '';
            $strAdditionalMimeType = '';
            $arrAdditionalMimeTypeParts = GeneralUtility::trimExplode(
                ',',
                $this->extensionConfiguration->getAdditionalMimeTypes(),
                true
            );

            foreach ($arrAdditionalMimeTypeParts as $strAdditionalMimeTypeItem) {
                list($strAdditionalFileExtension, $strAdditionalMimeType) = GeneralUtility::trimExplode(
                    '|',
                    $strAdditionalMimeTypeItem
                );
                if (!empty($strAdditionalFileExtension) && !empty($strAdditionalMimeType)) {
                    $strAdditionalFileExtension = mb_strtolower($strAdditionalFileExtension);
                    $arrMimeTypes[$strAdditionalFileExtension] = $strAdditionalMimeType;
                }
            }

            unset($strAdditionalFileExtension, $strAdditionalMimeType);
        }

        //TODO: Add hook to be able to manipulate and/or add mime types
        // Check if an specific MIME type is configured for this file extension
        if (array_key_exists($strFileExtension, $arrMimeTypes)) {
            $strMimeType = $arrMimeTypes[$strFileExtension];
        // files bigger than 32MB are now 'application/octet-stream' by default (getimagesize memory_limit problem)
        } elseif ($checkForImageFiles && ($this->fileSize < 1024 * 1024 * 32)) {
            $arrImageInfos = @getimagesize($this->file);
            $intImageType = (int)$arrImageInfos[2];
            $strMimeType = $intImageType === 0 ? 'application/octet-stream' : image_type_to_mime_type($intImageType);
        } else {
            $strMimeType = 'application/octet-stream';
        }

        return $strMimeType;
    }

    /*
     * HELPER METHODS
     *
     */

    /**
     * Extracts the file extension out of a complete file name.
     */
    protected function getFileExtensionByFilename(string $strFileName): string
    {
        return mb_strtolower(ltrim(mb_strrchr($strFileName, '.'), '.'));
    }

    /**
     * In some cases php needs the filesize as php_memory, so big files cannot
     * be transferred. This function mitigates this problem.
     */
    protected function readFileFactional(string $strFileName): bool
    {
        $chunksize = $this->extensionConfiguration->getOutputChunkSize(); // how many bytes per chunk
        $timeout = ini_get('max_execution_time');
        $bytes_sent = 0;
        $handle = fopen($strFileName, 'rb');
        if ($handle === false) {
            return false;
        }
        while (!feof($handle) && (!connection_aborted())) {
            set_time_limit($timeout);
            $buffer = fread($handle, $chunksize);
            print $buffer;
            $bytes_sent += $chunksize;
            ob_flush();
            flush();
            $this->logDownload(MathUtility::forceIntegerInRange($bytes_sent, 0, $this->fileSize));
        }

        return fclose($handle);
    }
}
