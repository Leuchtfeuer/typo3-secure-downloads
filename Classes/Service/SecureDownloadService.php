<?php
namespace Bitmotion\SecureDownloads\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Bitmotion GmbH (typo3-ext@bitmotion.de)
 *
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use Bitmotion\SecureDownloads\Parser\HtmlParser;
use Bitmotion\SecureDownloads\Configuration\ConfigurationManager;
use Bitmotion\SecureDownloads\Parser\HtmlParserDelegateInterface;
use Bitmotion\SecureDownloads\Request\RequestContext;
use Bitmotion\SecureDownloads\Resource\Publishing\ResourcePublisher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class SecureDownloadService
 * @package Bitmotion\SecureDownloads\Service
 */
class SecureDownloadService implements HtmlParserDelegateInterface
{
    /**
     * @var RequestContext
     */
    protected $requestContext;

    /**
     * @var ConfigurationManager
     */
    protected $configurationManager;

    /**
     * @var HtmlParser
     */
    protected $htmlParser;

    /**
     * @var ResourcePublisher
     */
    protected $resourcePublisher;

    /**
     * @param RequestContext $requestContext
     * @param ConfigurationManager $configurationManager
     */
    public function __construct($requestContext = null, $configurationManager = null)
    {
        $this->requestContext = $requestContext ?: new RequestContext();
        $this->configurationManager = $configurationManager ?: GeneralUtility::makeInstance(ConfigurationManager::class);
    }

    /**
     * This method is called by the frontend rendering hook contentPostProc->output
     *
     * @param array $parameters
     * @param TypoScriptFrontendController $typoScriptFrontendController
     */
    public function parseFE(array &$parameters, $typoScriptFrontendController)
    {
        // Parsing the content if not explicitly disabled
        if ($this->requestContext->isUrlRewritingEnabled()) {
            $typoScriptFrontendController->content = $this->getHtmlParser()->parse($typoScriptFrontendController->content);
        }
    }

    /**
     * Lazily instantiates the HTML parser
     * Must be called AFTER the configuration manager has been initialized
     *
     * @return \Bitmotion\SecureDownloads\Parser\HtmlParser
     */
    protected function getHtmlParser()
    {
        if (is_null($this->htmlParser)) {
            $this->htmlParser = new HtmlParser($this, [
                'domainPattern' => $this->configurationManager->getValue('domain'),
		'folderPatternRaw' => $this->configurationManager->getValue('securedDirsRaw'),
                'folderPattern' => $this->configurationManager->getValue('securedDirs'),
                'fileExtensionPattern' => $this->configurationManager->getValue('securedFiletypes'),
                'logLevel' => $this->configurationManager->getValue('debug'),
            ]);
        }

        return $this->htmlParser;
    }

    /**
     * Method kept for compatibility
     *
     * @param string $html
     *
     * @return string
     */
    public function parseContent($html)
    {
        return $this->getHtmlParser()->parse($html);
    }

    /**
     * Method kept for compatibility
     *
     * @param string $originalUri
     *
     * @return string
     */
    public function makeSecure($originalUri)
    {
        return $this->publishResourceUri($originalUri);
    }

    /**
     * Transforms a relative file URL to a secure download protected URL
     *
     * @param string $originalUri
     *
     * @return string
     */
    public function publishResourceUri($originalUri)
    {
        $transformedUri = $this->getResourcePublisher()->publishResourceUri(rawurldecode($originalUri));

        // Hook for makeSecure:
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/secure_downloads/Classes/Service/SecureDownloadService.php']['makeSecure'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/secure_downloads/Classes/Service/SecureDownloadService.php']['makeSecure'] as $_funcRef) {
                $transformedUri = GeneralUtility::callUserFunction($_funcRef, $transformedUri, $this);
            }
        }
        // Hook for makeSecure: (old class name, for compatibility reasons)
        if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/secure_downloads/class.tx_securedownloads.php']['makeSecure'])) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/secure_downloads/class.tx_securedownloads.php']['makeSecure'] as $_funcRef) {
                $transformedUri = GeneralUtility::callUserFunction($_funcRef, $transformedUri, $this);
            }
        }

        return $transformedUri;
    }

    /**
     * Lazily intatiates the resource publisher
     *
     * @return ResourcePublisher
     */
    protected function getResourcePublisher()
    {
        if (is_null($this->resourcePublisher)) {
            $this->resourcePublisher = GeneralUtility::makeInstance(ResourcePublisher::class);
        }

        return $this->resourcePublisher;
    }
}
