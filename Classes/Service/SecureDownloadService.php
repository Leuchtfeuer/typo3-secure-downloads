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

use Bitmotion\SecureDownloads\Configuration\ConfigurationManager;
use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Bitmotion\SecureDownloads\Parser\HtmlParser;
use Bitmotion\SecureDownloads\Parser\HtmlParserDelegateInterface;
use Bitmotion\SecureDownloads\Request\RequestContext;
use Bitmotion\SecureDownloads\Resource\Publishing\ResourcePublisher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class SecureDownloadService implements HtmlParserDelegateInterface
{
    protected $requestContext;

    /**
     * @var HtmlParser
     */
    protected $htmlParser;

    /**
     * @var ResourcePublisher
     */
    protected $resourcePublisher;

    public function __construct(RequestContext $requestContext = null)
    {
        $this->requestContext = $requestContext ?? GeneralUtility::makeInstance(RequestContext::class);
    }

    /**
     * This method is called by the frontend rendering hook contentPostProc->output
     */
    public function parseFE(array &$parameters, TypoScriptFrontendController $typoScriptFrontendController)
    {
        // Parsing the content if not explicitly disabled
        if ($this->requestContext->isUrlRewritingEnabled()) {
            // TODO: $typoScriptFrontendController->content is deprecated since TYPO3 9.0
            $typoScriptFrontendController->content = $this->getHtmlParser()->parse($typoScriptFrontendController->content);
        }
    }

    /**
     * Lazily instantiates the HTML parser
     * Must be called AFTER the configuration manager has been initialized
     */
    protected function getHtmlParser(): HtmlParser
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
     * Method kept for compatibility
     */
    public function parseContent(string $html): string
    {
        return $this->getHtmlParser()->parse($html);
    }

    /**
     * Method kept for compatibility
     */
    public function makeSecure(string $originalUri): string
    {
        return $this->publishResourceUri($originalUri);
    }

    /**
     * Transforms a relative file URL to a secure download protected URL
     */
    public function publishResourceUri(string $originalUri): string
    {
        $transformedUri = $this->getResourcePublisher()->publishResourceUri(rawurldecode($originalUri));

        // Hook for makeSecure:
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/secure_downloads/Classes/Service/SecureDownloadService.php']['makeSecure'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/secure_downloads/Classes/Service/SecureDownloadService.php']['makeSecure'] as $_funcRef) {
                $transformedUri = GeneralUtility::callUserFunction($_funcRef, $transformedUri, $this);
            }
        }

        return $transformedUri;
    }

    /**
     * Lazily intatiates the resource publisher
     */
    protected function getResourcePublisher(): ResourcePublisher
    {
        if (is_null($this->resourcePublisher)) {
            $this->resourcePublisher = GeneralUtility::makeInstance(ResourcePublisher::class);
        }

        return $this->resourcePublisher;
    }
}
