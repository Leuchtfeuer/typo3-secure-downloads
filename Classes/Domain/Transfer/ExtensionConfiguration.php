<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\Domain\Transfer;

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

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ExtensionConfiguration implements SingletonInterface
{
    const SECURED_FILE_TYPES_WILDCARD = '*';

    private $additionalMimeTypes = 'txt|text/plain,html|text/html';

    private $cachetimeadd = 3600;

    /**
     * @deprecated Will be removed in version 5. Use PSR-3 Logger instead.
     */
    private $debug = 0;

    private $domain = 'http://mydomain.com/|http://my.other.domain.org/';

    private $enableGroupCheck = false;

    private $excludeGroups = '';

    private $forcedownload = false;

    private $forcedownloadtype = 'odt|pptx?|docx?|xlsx?|zip|rar|tgz|tar|gz';

    private $groupCheckDirs = '';

    private $log = false;

    private $outputChunkSize = 1048576;

    private $outputFunction = 'readfile';

    private $securedDirs = 'typo3temp|fileadmin';

    private $securedFiletypes = 'pdf|jpe?g|gif|png|odt|pptx?|docx?|xlsx?|zip|rar|tgz|tar|gz';

    private $linkPrefix = 'download';

    private $tokenPrefix = 'sdl-';

    /**
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function __construct()
    {
        $configuration = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get('secure_downloads');

        if ($configuration) {
            $this->setPropertiesFromConfiguration($configuration);
        }
    }

    protected function setPropertiesFromConfiguration(array $configuration): void
    {
        foreach ($configuration as $key => $value) {
            if (property_exists(__CLASS__, $key)) {
                $this->$key = $value;
            }
        }
    }

    public function getAdditionalMimeTypes(): string
    {
        return trim($this->additionalMimeTypes);
    }

    public function getCacheTimeAdd(): int
    {
        return (int)$this->cachetimeadd;
    }

    /**
     * @deprecated Will be removed in version 5. Use PSR-3 Logger instead.
     */
    public function getDebug(): int
    {
        trigger_error('Method getDebug() will be removed in version 5..', E_USER_DEPRECATED);

        return (int)$this->debug;
    }

    public function getDomain(): string
    {
        return trim($this->domain);
    }

    public function isEnableGroupCheck(): bool
    {
        return (bool)$this->enableGroupCheck;
    }

    public function getExcludeGroups(): string
    {
        return trim($this->excludeGroups);
    }

    public function isForceDownload(): bool
    {
        return (bool)$this->forcedownload;
    }

    public function getForceDownloadTypes(): string
    {
        return trim($this->forcedownloadtype);
    }

    public function getGroupCheckDirs(): string
    {
        return trim($this->groupCheckDirs);
    }

    public function isLog(): bool
    {
        return (bool)$this->log;
    }

    public function getOutputChunkSize(): int
    {
        $units = ['', 'K', 'M', 'G'];
        $memoryLimit = ini_get('memory_limit');

        if (!is_numeric($memoryLimit)) {
            $suffix = strtoupper(substr($memoryLimit, -1));
            $exponent = array_flip($units)[$suffix] ?? 1;
            $memoryLimit = (int)$memoryLimit * (1024 ** $exponent);
        }

        // Set max. chunk size to php memory limit - 64 kB
        $maxChunkSize = $memoryLimit - 64 * 1024;

        return (int)(($this->outputChunkSize > $maxChunkSize) ? $maxChunkSize : $this->outputChunkSize);
    }

    public function getOutputFunction(): string
    {
        return trim($this->outputFunction);
    }

    public function getSecuredDirs(): string
    {
        return trim($this->securedDirs);
    }

    public function getSecuredFileTypes(): string
    {
        return trim($this->securedFiletypes);
    }

    public function getLinkPrefix(): string
    {
        return trim($this->linkPrefix, '/');
    }

    public function getTokenPrefix(): string
    {
        return trim($this->tokenPrefix, '/');
    }
}
