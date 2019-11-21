<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\Service;

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

use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Bitmotion\SecureDownloads\Parser\HtmlParser;
use Bitmotion\SecureDownloads\Parser\HtmlParserDelegateInterface;
use Bitmotion\SecureDownloads\Resource\Publishing\ResourcePublisher;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class SecureDownloadService implements HtmlParserDelegateInterface, SingletonInterface
{
    protected $htmlParser;

    protected $resourcePublisher;

    protected $extensionConfiguration;

    protected $securedFileTypesPattern;

    public function __construct(ResourcePublisher $resourcePublisher = null)
    {
        $this->resourcePublisher = $resourcePublisher ?? GeneralUtility::makeInstance(ObjectManager::class)->get(ResourcePublisher::class);
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $this->securedFileTypesPattern = sprintf('/^(%s)$/i', $this->extensionConfiguration->getSecuredFileTypes());
    }

    /**
     * This method is called by the frontend rendering hook contentPostProc->output
     */
    public function parseFE(array &$parameters, TypoScriptFrontendController $typoScriptFrontendController)
    {
        $typoScriptFrontendController->content = $this->getHtmlParser()->parse($typoScriptFrontendController->content);
    }

    /**
     * Lazily instantiates the HTML parser
     * Must be called AFTER the configuration manager has been initialized
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
     */
    public function publishResourceUri(string $originalUri): string
    {
        $transformedUri = $this->resourcePublisher->publishResourceUri(rawurldecode($originalUri));

        // Hook for makeSecure:
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/secure_downloads/Classes/Service/SecureDownloadService.php']['makeSecure'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/secure_downloads/Classes/Service/SecureDownloadService.php']['makeSecure'] as $_funcRef) {
                $transformedUri = GeneralUtility::callUserFunction($_funcRef, $transformedUri, $this);
            }
        }

        return $transformedUri;
    }

    /**
     * Check whether file is located underneath a secured folder and file extension should matches file types pattern.
     */
    public function pathShouldBeSecured(string $publicUrl): bool
    {
        foreach (explode('|', $this->extensionConfiguration->getSecuredDirs()) as $securedDir) {
            if (strpos($publicUrl, $securedDir) === 0) {
                $fileExtension = pathinfo($publicUrl, PATHINFO_EXTENSION);
                if (preg_match($this->securedFileTypesPattern, $fileExtension)) {
                    return true;
                }
            }
        }

        return false;
    }
}
