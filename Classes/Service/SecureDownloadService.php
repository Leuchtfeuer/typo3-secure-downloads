<?php
namespace Bitmotion\NawSecuredl\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2013 Dietrich Heise (typo3-ext(at)bitmotion.de)
 *  (c) 2009-2013 Helmut Hummel <helmut.hummel@typo3.org>
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

use Bitmotion\NawSecuredl\Parser\HtmlParser;
use Bitmotion\NawSecuredl\Configuration\ConfigurationManager;
use Bitmotion\NawSecuredl\Parser\HtmlParserDelegateInterface;
use Bitmotion\NawSecuredl\Request\RequestContext;
use Bitmotion\NawSecuredl\Resource\Publishing\ResourcePublisher;

/**
 * @author Dietrich Heise <typo3-ext(at)bitmotion.de>
 * @author Helmut Hummel <helmut.hummel@typo3.org>
 */
class SecureDownloadService implements HtmlParserDelegateInterface {
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
	public function __construct($requestContext = NULL, $configurationManager = NULL) {
		$this->requestContext = $requestContext ?: new RequestContext();
		$this->configurationManager = $configurationManager ?: \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Bitmotion\\NawSecuredl\\Configuration\\ConfigurationManager');
	}

	/**
	 * This method is called by the frontend rendering hook contentPostProc->output
	 *
	 * @param array $parameters
	 * @param \tslib_fe $typoScriptFrontendController
	 */
	public function parseFE(array &$parameters, $typoScriptFrontendController) {
		// Parsing the content if not explicitly disabled
		if ($this->requestContext->isUrlRewritingEnabled()) {
			$typoScriptFrontendController->content = $this->getHtmlParser()->parse($typoScriptFrontendController->content);
		}
	}

	/**
	 * Transforms a relative file URL to a secure download protected URL
	 *
	 * @param string $originalUri
	 * @return string
	 */
	public function publishResourceUri($originalUri) {
		$transformedUri = $this->getResourcePublisher()->publishResourceUri(rawurldecode($originalUri));

		// Hook for makeSecure:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/Classes/Service/SecureDownloadService.php']['makeSecure'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/Classes/Service/SecureDownloadService.php']['makeSecure'] as $_funcRef)   {
				$transformedUri = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $transformedUri, $this);
			}
		}
		// Hook for makeSecure: (old class name, for compatibility reasons)
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl.php']['makeSecure'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl.php']['makeSecure'] as $_funcRef)   {
				$transformedUri = \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($_funcRef, $transformedUri, $this);
			}
		}

		return $transformedUri;
	}

	/**
	 * Method kept for compatibility
	 *
	 * @param string $html
	 * @return string
	 */
	public function parseContent($html) {
		return $this->getHtmlParser()->parse($html);
	}

	/**
	 * Method kept for compatibility
	 *
	 * @param string $originalUri
	 * @return string
	 */
	public function makeSecure($originalUri) {
		return $this->publishResourceUri($originalUri);
	}

	/**
	 * Lazily instantiates the HTML parser
	 * Must be called AFTER the configuration manager has been initialized
	 *
	 * @return \Bitmotion\NawSecuredl\Parser\HtmlParser
	 */
	protected function getHtmlParser() {
		if (is_null($this->htmlParser)) {
			$this->htmlParser = new HtmlParser(
				$this,
				array(
					'domainPattern' => $this->configurationManager->getValue('domain'),
					'folderPattern' => $this->configurationManager->getValue('securedDirs'),
					'fileExtensionPattern' => $this->configurationManager->getValue('filetype'),
					'logLevel' => $this->configurationManager->getValue('debug'),
				)
			);
		}
		return $this->htmlParser;
	}

	/**
	 * Lazily intatiates the publishing target
	 *
	 * @return ResourcePublisher
	 */
	protected function getResourcePublisher() {
		if (is_null($this->resourcePublisher)) {
			$this->resourcePublisher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Bitmotion\\NawSecuredl\\Resource\\Publishing\\ResourcePublisher');
		}
		return $this->resourcePublisher;
	}
}