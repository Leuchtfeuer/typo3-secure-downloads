<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Service;

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

use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Bitmotion\SecureDownloads\Factory\SecureLinkFactory;
use Bitmotion\SecureDownloads\Parser\HtmlParser;
use Bitmotion\SecureDownloads\Parser\HtmlParserDelegateInterface;
use Bitmotion\SecureDownloads\Utility\HookUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class SecureDownloadService implements HtmlParserDelegateInterface, SingletonInterface
{
    protected $htmlParser;

    protected $extensionConfiguration;

    protected $securedFileTypesPattern;

    protected $securedDirectoriesPattern;

    public function __construct()
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $this->securedFileTypesPattern = sprintf('/^(%s)$/i', $this->extensionConfiguration->getSecuredFileTypes());
        $this->securedDirectoriesPattern = sprintf('/^(%s)/i', str_replace('/', '\/', $this->extensionConfiguration->getSecuredDirs()));
    }

    /**
     * This method is called by the frontend rendering hook contentPostProc->output
     *
     * @deprecated Parsing the generated HTML is deprecated. All public URLs to files should be retrieved by TYPO3 API.
     */
    public function parseFE(array &$parameters, TypoScriptFrontendController $typoScriptFrontendController)
    {
        $typoScriptFrontendController->content = $this->getHtmlParser()->parse($typoScriptFrontendController->content);
    }

    /**
     * Lazily instantiates the HTML parser
     * Must be called AFTER the configuration manager has been initialized
     *
     * @deprecated Parsing the generated HTML is deprecated. All public URLs to files should be retrieved by TYPO3 API.
     */
    public function getHtmlParser(): HtmlParser
    {
        if (is_null($this->htmlParser)) {
            $extensionConfiguration = new ExtensionConfiguration();

            $this->htmlParser = new HtmlParser($this, [
                'domainPattern' => $extensionConfiguration->getDomain(),
                'folderPattern' => $extensionConfiguration->getSecuredDirs(),
                'fileExtensionPattern' => $extensionConfiguration->getSecuredFileTypes(),
                'logLevel' => $extensionConfiguration->getDebug(),
            ]);
        }

        return $this->htmlParser;
    }

    /**
     * @deprecated Will be removed with version 5. Use $this->publishResourceUri instead.
     */
    public function makeSecure(string $originalUri): string
    {
        trigger_error('Method makeSecure() will be removed in version 5. Use publishResourceUri() instead.', E_USER_DEPRECATED);

        return $this->publishResourceUri($originalUri);
    }

    /**
     * Transforms a relative file URL to a secure download protected URL
     *
     * @deprecated Will be removed in version 5.
     */
    public function publishResourceUri(string $originalUri): string
    {
        $secureLinkFactory = GeneralUtility::makeInstance(SecureLinkFactory::class, rawurldecode($originalUri));
        $transformedUri = $secureLinkFactory->getUrl();

        // Hook for makeSecure:
        // TODO: This hook is deprecated and will be removed in version 5.
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/secure_downloads/Classes/Service/SecureDownloadService.php']['makeSecure'])) {
            trigger_error('Hook name ext/secure_downloads/Classes/Service/SecureDownloadService.php is deprecated. Use bitmotion.secure_downloads.downloadService instead.', E_USER_DEPRECATED);
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['bitmotion']['secure_downloads']['downloadService']['makeSecure'] = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/secure_downloads/Classes/Service/SecureDownloadService.php']['makeSecure'];
        }
        HookUtility::executeHook('downloadService', 'makeSecure', $transformedUri, $this);

        return $transformedUri;
    }

    /**
     * Check whether file is located underneath a secured folder and file extension should matches file types pattern.
     */
    public function pathShouldBeSecured(string $publicUrl): bool
    {
        if ($this->folderShouldBeSecured($publicUrl)) {
            if ($this->extensionConfiguration->getSecuredFileTypes() === ExtensionConfiguration::FILE_TYPES_WILDCARD) {
                return true;
            }

            $fileExtension = pathinfo($publicUrl, PATHINFO_EXTENSION);
            if (preg_match($this->securedFileTypesPattern, $fileExtension)) {
                return true;
            }
        }

        return false;
    }

    public function folderShouldBeSecured(string $publicUrl): bool
    {
        return (bool)preg_match($this->securedDirectoriesPattern, $publicUrl);
    }

    public function getResourceUrl(string $publicUrl): string
    {
        $secureLinkFactory = GeneralUtility::makeInstance(SecureLinkFactory::class, rawurldecode($publicUrl));

        return $secureLinkFactory->getUrl();
    }
}
