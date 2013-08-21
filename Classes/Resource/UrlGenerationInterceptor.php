<?php
namespace Bitmotion\NawSecuredl\Resource;

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

use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class UrlGenerationInterceptor
 * @package Bitmotion\NawSecuredl\Resource
 */
class UrlGenerationInterceptor {

	/**
	 * @param ResourceStorage $storage
	 * @param AbstractDriver $driver
	 * @param ResourceInterface $resourceObject
	 * @param boolean $relativeToCurrentScript
	 * @param array $urlData
	 */
	public function getPublicUrl(ResourceStorage $storage, AbstractDriver $driver, ResourceInterface $resourceObject, $relativeToCurrentScript, array $urlData) {
		$publicUrl = $driver->getPublicUrl($resourceObject, FALSE);

		$extensionConfiguration = $this->getSecureDlExtensionConfiguration();

		if (preg_match('/(('. $this->softQuoteExpression($extensionConfiguration['securedDirs']) . ')+?\/.*?(?:(?i)' . ($extensionConfiguration['filetype']) . '))/i', $publicUrl, $matchedUrls)) {
			if (is_array($matchedUrls)){
				if ($matchedUrls[0] == $publicUrl){
					$objSecureDownloads = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance
						('Bitmotion\NawSecuredl\Service\SecureDownloadService');
					$publicUrl = $objSecureDownloads->makeSecure($publicUrl);
					// If requested, make the path relative to the current script in order to make it possible
					// to use the relative file
					if ($relativeToCurrentScript) {
						$publicUrl = PathUtility::getRelativePathTo(PathUtility::dirname((PATH_site . $publicUrl))) . PathUtility::basename($publicUrl);
					}
				}
			}
		}

		$urlData['publicUrl'] = $publicUrl;

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

	/**
	 * Get extension configuration
	 *
	 * @return array
	 */
	protected function getSecureDlExtensionConfiguration() {
		return unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['naw_securedl']);
	}
}