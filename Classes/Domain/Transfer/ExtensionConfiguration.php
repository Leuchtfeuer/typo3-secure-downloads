<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Domain\Transfer;

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

use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Transfer object for getting extension configuration.
 */
class ExtensionConfiguration implements SingletonInterface
{
    const FILE_TYPES_WILDCARD = '*';

    const OUTPUT_READ_FILE = 'readfile';

    /**
     * @deprecated Will be removed in version 5. Use "stream" instead.
     */
    const OUTPUT_READ_FILE_CHUNKED = 'readfile_chunked';

    const OUTPUT_STREAM = 'stream';

    const OUTPUT_PASS_THRU = 'fpassthru';

    const OUTPUT_NGINX = 'x-accel-redirect';

    /**
     * @deprecated Will be removed in version 5.
     */
    private $additionalMimeTypes = 'txt|text/plain,html|text/html';

    /**
     * The value will be added to configured cache lifetime of the page, where the resource is embedded in.
     * If there is no page, a default link timeout will be added.
     *
     * @var int The additional timeout for generated tokens in seconds.
     */
    private $cachetimeadd = 3600;

    /**
     * Do only change this configuration option, if your TYPO3 instance is running in a subfolder or you are using a SSL reverse
     * proxy to map TYPO3 into a virtual subfolder.
     *
     * @var string The document root path.
     */
    protected $documentRootPath = '/';

    /**
     * @deprecated Will be removed in version 5. Use PSR-3 Logger instead.
     */
    private $debug = 0;

    /**
     * @deprecated Will be removed in version 5. You should consider to user the TYPO3 API.
     */
    private $domain = 'http://mydomain.com/|http://my.other.domain.org/';

    /**
     * If enabled, given groups in token data will used to match groups of actual user.
     * Group check is disabled be default.
     *
     * @var bool True if a group check is to take place.
     */
    private $enableGroupCheck = false;

    /**
     * Identifier of groups, which should not be respected in group check.
     * Will only be used, when group check is enabled.
     *
     * @var string Comma separated list of groups, which should be excluded from group check.
     */
    private $excludeGroups = '-1,0';

    /**
     * The group check can be limited to certain directories. This will only be used if group check is enabled.
     * If group check is enabled and group check dirs is empty, all secured directories will be handled by group check.
     *
     * @var string The directories which should be included in group check.
     */
    private $groupCheckDirs = '';

    /**
     * If enabled, files are only delivered if the user groups exactly match those of the secured link.
     *
     * @var bool Ture if the group check should be strict.
     */
    private $strictGroupCheck = false;

    /**
     * Download of specific file types can be forced.
     *
     * @var bool True if the download of configured file types should be forced.
     */
    private $forcedownload = false;

    /**
     * This pipe separated list will be used to figure out, whether a file should be forced to download or not.
     * The force download type is only be used if force download is set to true.
     *
     * @var string File types that should be forced to download.
     */
    private $forcedownloadtype = 'odt|pptx?|docx?|xlsx?|zip|rar|tgz|tar|gz';

    /**
     * Secured files downloaded can be tracked. If you want so, you can enable this option. In addition, a dedicated backend
     * module will be enabled where you can find the data.
     *
     * @var bool If true, the log module will be enabled.
     */
    private $log = false;

    /**
     * If files should be delivered chunked, this size will be used to denominate the file.
     *
     * @var int Chunk size in byte.
     * @deprecated Will be removed in version 5. A recommended default value of 4096 bytes will be set for streams.
     */
    private $outputChunkSize = 1048576;

    /**
     * The output function, which should be used to deliver secured files from the server to the web browser of the user.
     *
     * @var string One of "readfile", "readfile_chunked", "fpassthru", "stream" (default) or "x-accel-redirect"
     */
    private $outputFunction = self::OUTPUT_STREAM;

    /**
     * Only files located in these folders are secured. Folders are separated by a pipe. Also, all subdirectories are included.
     *
     * @var string Directories which should be secured.
     */
    private $securedDirs = 'typo3temp|fileadmin';

    /**
     * A pipe separated list of file types, that should be secured. Files will only be secured when they are located underneath
     * one of the configured secured directories. Optional characters can be marked with a question mark. For example: The file
     * type "jpe?g" will match both, "jpg" and "jpeg".
     *
     * @var string File types that should be secured.
     */
    private $securedFiletypes = 'pdf|jpe?g|gif|png|odt|pptx?|docx?|xlsx?|zip|rar|tgz|tar|gz';

    /**
     * This prefix will be appended to your domain. All secured links are structured as follows:
     * https://www.mydomain.com/[$linkPrefix]/[$tokenPrefix][JWT]/my_secured_file.jpg
     * https://www.Leuchtfeuer.com/securedl/sdl-[JSON Web Token]/bitmotion_whirl.svg
     *
     * @var string Prefix of secured links will be appended to the domain.
     */
    private $linkPrefix = 'securedl';

    /**
     * Prefix before the Json web token. This value might be empty. All secured links are structured as follows:
     * https://www.mydomain.com/[$linkPrefix]/[$tokenPrefix][JWT]/my_secured_file.jpg
     * https://www.Leuchtfeuer.com/securedl/sdl-[JSON Web Token]/bitmotion_whirl.svg
     *
     * @var string Prefix of tokens.
     */
    private $tokenPrefix = 'sdl-';

    /**
     * Path to protected storage for nginx x-accel-redirect delivery method
     *
     * @var string The path to the protected Storage.
     */
    private $protectedPath = '';

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

    /**
     * Remove invalid configuration from the extension configuration array and map values to properties of this class.
     *
     * @param array $configuration The extension configuration.
     */
    protected function setPropertiesFromConfiguration(array $configuration): void
    {
        foreach ($configuration as $key => $value) {
            if (property_exists(__CLASS__, $key)) {
                $this->$key = $value;
            }
        }
    }

    /**
     * @deprecated Will be removed in version 5.
     */
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
        trigger_error('Method getDebug() will be removed in version 5.', E_USER_DEPRECATED);

        return (int)$this->debug;
    }

    /**
     * @deprecated Will be removed in version 5. You should consider to user the TYPO3 API.
     */
    public function getDomain(): string
    {
        trigger_error('Method getDomain() will be removed in version 5.', E_USER_DEPRECATED);

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

    /**
     * @deprecated Will be removed in version 5.
     */
    public function getOutputChunkSize(): int
    {
        $maxChunkSize = $this->getMaxChunkSize();

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

    public function getProtectedPath(): string
    {
        return $this->protectedPath;
    }

    public function isStrictGroupCheck(): bool
    {
        return (bool)$this->strictGroupCheck;
    }

    public function getDocumentRootPath(): string
    {
        return $this->documentRootPath;
    }

    /**
     * Prevents chunk size to be greater than allowed PHP memory limit.
     *
     * @return int The maximum chunk size.
     * @deprecated Will be removed in version 5.
     */
    protected function getMaxChunkSize(): int
    {
        $units = ['', 'K', 'M', 'G'];
        $memoryLimit = ini_get('memory_limit');

        if (!is_numeric($memoryLimit)) {
            $suffix = strtoupper(substr($memoryLimit, -1));
            $exponent = array_flip($units)[$suffix] ?? 1;
            $memoryLimit = (int)$memoryLimit * (1024 ** $exponent);
        }

        // Set max. chunk size to php memory limit - 64 kB
        return $memoryLimit - 64 * 1024;
    }
}
