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

use Bitmotion\NawSecuredl\Request\RequestContext;
use TYPO3\CMS\Core\Resource\ResourceInterface;

/**
 * Class PhpDeliveryProtectedResourcePublishingTarget
 * @package Bitmotion\NawSecuredl\Resource\Publishing
 */
class PhpDeliveryProtectedResourcePublishingTarget extends AbstractResourcePublishingTarget {
	/**
	 * @var RequestContext
	 */
	protected $requestContext;

	/**
	 * Publishes a persistent resource to the web accessible resources directory
	 *
	 * @param ResourceInterface $resource The resource to publish
	 * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or the resource could not be published for other reasons
	 */
	public function publishResource(ResourceInterface $resource) {
		if ($this->isSourcePathInDocumentRoot()) {
			if (!$this->isPubliclyAvailable($resource)) {
				$publicUrl = $this->buildPhpDownloadDeliveryUrl($this->getResourceUri($resource));
			} else {
				// Nothing to do
			}
		} else {
			// TODO: Maybe implement this case?
		}
		return isset($publicUrl) ? $publicUrl : FALSE;
	}

	/**
	 * Builds a URI which uses a PHP Script to access the resource
	 *
	 * @param string $resourceUri
	 * @return string
	 */
	public function buildPhpDownloadDeliveryUrl($resourceUri) {
//return 'http://localhost/phpMyAdmin/themes/original/img/logo_left.png'; // TEST URL TODO: remove
		$userId = $this->getRequestContext()->getUserId();
		$userGroupIds = $this->getRequestContext()->getUserGroupIds();

		$cacheTimeToAdd = $this->configurationManager->getValue('cachetimeadd');

		if ($this->getRequestContext()->getCacheLifetime() === 0){
			$timeout = 86400 + $GLOBALS['EXEC_TIME'] + $cacheTimeToAdd;
		} else {
			$timeout = $this->getRequestContext()->getCacheLifetime() + $GLOBALS['EXEC_TIME'] + $cacheTimeToAdd;
		}

		$hash = $this->getHash($userId . implode(',', $userGroupIds) . $resourceUri . $timeout);

		$linkFormat = $this->configurationManager->getValue('linkFormat');

		// Parsing the link format, and return this instead (an flexible link format is useful for mod_rewrite tricks ;)
		if (!is_null($linkFormat) || strpos($linkFormat, '###FEGROUPS###') === FALSE) {
			$linkFormat = '/index.php?eID=tx_nawsecuredl&u=###FEUSER###&g=###FEGROUPS###&t=###TIMEOUT###&hash=###HASH###&file=###FILE###';
		}

		$tokens = array('###FEUSER###', '###FEGROUPS###', '###FILE###', '###TIMEOUT###', '###HASH###');
		$replacements = array($userId, rawurlencode(implode(',', $userGroupIds)), urlencode($resourceUri), $timeout, $hash);
		$downloadUri = str_replace($tokens, $replacements, $linkFormat);

		// TODO: Add signal

		return $downloadUri;
	}

	/**
	 * Checks if a resource which lies in document root is really publicly available
	 * This is currently only done by checking configured secure paths, not by requesting the resources
	 *
	 * @param ResourceInterface $resource
	 * @return bool
	 */
	protected function isPubliclyAvailable(ResourceInterface $resource) {
		$resourceUri = $this->getResourceUri($resource);
		$securedFoldersExpression = $this->configurationManager->getValue('securedDirs');
		$fileExtensionExpression = $this->configurationManager->getValue('filetype');
		// TODO: maybe check if the resource is available without authentication by doing a head request
		return !(preg_match('/(('. $this->softQuoteExpression($securedFoldersExpression) . ')+?\/.*?(?:(?i)' . ($fileExtensionExpression) . '))/i', $resourceUri, $matchedUrls)
			&& is_array($matchedUrls)
			&& $matchedUrls[0] === $resourceUri);
	}

	/**
	 * @return RequestContext
	 */
	protected function getRequestContext() {
		if ($this->requestContext === NULL) {
			$this->buildRequestContext();
		}
		return $this->requestContext;
	}

	/**
	 * Creates the request context
	 */
	protected function buildRequestContext() {
		$this->requestContext = new RequestContext();
	}




/*
 * HELPER MEHTODS
 * TODO: Refactor them out
 */




	/**
	 * @param $string
	 * @return string
	 */
	protected function getHash($string) {
		return \t3lib_div::hmac($string);
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