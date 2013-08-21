<?php
namespace Bitmotion\NawSecuredl\Resource\Publishing;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Helmut Hummel (helmut.hummel@typo3.org)
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
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class ResourcePublisher
 * @package Bitmotion\NawSecuredl\Resource\Publishing
 */
class ResourcePublisher implements ResourcePublisherInterface,SingletonInterface {

	/**
	 * @var string
	 */
	protected $baseUri;

	/**
	 * @var \Bitmotion\NawSecuredl\Configuration\ConfigurationManager
	 * @inject
	 */
	protected $configurationManager;

	/**
	 * Returns the web URI pointing to the published resource
	 *
	 * @param ResourceInterface $resource The resource to publish
	 * @return string Either the web URI of the published resource or FALSE if the resource source file doesn't exist or the resource could not be published for other reasons
	 */
	public function getResourceWebUri(ResourceInterface $resource) {
		return $this->publishResource($resource);
	}

	/**
	 * Publishes a persistent resource to the web accessible resources directory
	 *
	 * @param ResourceInterface $resource The resource to publish
	 * @return string Either the web URI of the published resource or FALSE if the resource source file doesn't exist or the resource could not be published for other reasons
	 */
	public function publishResource(ResourceInterface $resource) {
		$relativeLocalPath = PathUtility::getCanonicalPath($this->baseUri . '/' . $resource->getIdentifier());

		$securedFoldersExpression = $this->configurationManager->getValue('securedDirs');
		$fileExtensionExpression = $this->configurationManager->getValue('filetype');

		// TODO: Make nicer, move URL generation code from service in this class
		if (preg_match('/(('. $this->softQuoteExpression($securedFoldersExpression) . ')+?\/.*?(?:(?i)' . ($fileExtensionExpression) . '))/i', $relativeLocalPath, $matchedUrls)) {
			if (is_array($matchedUrls)){
				if ($matchedUrls[0] == $relativeLocalPath){
					$objSecureDownloads = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance
						('Bitmotion\NawSecuredl\Service\SecureDownloadService');
					$publicUrl = $objSecureDownloads->makeSecure($relativeLocalPath);
				}
			}
		}

		return isset($publicUrl) ? $publicUrl : FALSE;
	}

	/**
	 * @param string $baseUri
	 */
	public function setBaseUri($baseUri) {
		$this->baseUri = $baseUri;
	}

	/**
	 * @return string
	 */
	public function getBaseUri() {
		return $this->baseUri;
	}


	/**
	 * Quotes special some characters for the regular expression.
	 * Leave braces and brackets as is to have more flexibility in configuration.
	 *
	 * @param string $string
	 * @return string
	 */
	protected function softQuoteExpression($string) {
		return str_replace(
			array(
				'\\',
				' ',
				'/',
				'.',
				':'
			),
			array(
				'\\\\',
				'\ ',
				'\/',
				'\.',
				'\:'
			),
			$string
		);
	}
}