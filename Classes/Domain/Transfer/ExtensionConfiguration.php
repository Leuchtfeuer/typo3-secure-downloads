<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Domain\Transfer;

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

    const OUTPUT_STREAM = 'stream';

    const OUTPUT_NGINX = 'x-accel-redirect';

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
     * The output function, which should be used to deliver secured files from the server to the web browser of the user.
     *
     * @var string One of "stream" (default) or "x-accel-redirect"
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
     * @var string The path to the protected storage.
     */
    private $protectedPath = '';

    /**
     * If enabled, a secure downloads file storage is created and automatically added to your system. Also, an .htaccess
     * file will be put into that directory. If you are using an nginx web server, you have to deny the access to this path
     * manually.
     *
     * @var bool True if a file storage should be created.
     */
    private $createFileStorage = false;

    /**
     * If this option is activated, valid links are generated for users who are not logged in. If this option is deactivated
     * unregistered users (user ID = 0) will not be able to access secured files.
     *
     * @var bool If true, not logged in users are able to access secured files
     */
    private $allowPublicAccess = true;

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

    public function getCacheTimeAdd(): int
    {
        return (int)$this->cachetimeadd;
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

    public function getOutputFunction(): string
    {
        return trim($this->outputFunction);
    }

    public function getSecuredDirs(): string
    {
        return trim($this->securedDirs);
    }

    public function getSecuredDirectoriesPattern(): string
    {
        return sprintf('#^(%s)#i', $this->getSecuredDirs());
    }

    public function getSecuredFileTypes(): string
    {
        return trim($this->securedFiletypes);
    }

    public function getSecuredFileTypesPattern(string $pattern = '#^(%s)$#i'): string
    {
        return sprintf($pattern, $this->getSecuredFileTypes());
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
        return trim($this->protectedPath);
    }

    public function isStrictGroupCheck(): bool
    {
        return (bool)$this->strictGroupCheck;
    }

    public function getDocumentRootPath(): string
    {
        return trim($this->documentRootPath);
    }

    public function isCreateFileStorage(): bool
    {
        return (bool)$this->createFileStorage;
    }

    public function isAllowPublicAccess(): bool
    {
        return (bool)$this->allowPublicAccess;
    }
}
