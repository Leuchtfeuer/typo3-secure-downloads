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

use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\PathUtility;
use Bitmotion\NawSecuredl\Configuration\ConfigurationManager;
use Bitmotion\NawSecuredl\Request\RequestContext;

/**
 * Class AbstractResourcePublishingTarget
 * @package Bitmotion\NawSecuredl\Resource\Publishing
 */
abstract class AbstractResourcePublishingTarget implements ResourcePublishingTargetInterface, \TYPO3\CMS\Core\SingletonInterface {
	/**
	 * @var string
	 */
	protected $resourcesBaseUri;

	/**
	 * @var string
	 */
	protected $resourcesSourcePath;

	/**
	 * @var string
	 */
	protected $resourcesPublishingPath;

	/**
	 * @var RequestContext
	 */
	protected $requestContext;

	/**
	 * @var \Bitmotion\NawSecuredl\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @param \Bitmotion\NawSecuredl\Configuration\ConfigurationManager $configurationManager
	 */
	public function injectConfigurationManager(ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Returns the web URI pointing to the published resource
	 *
	 * @param ResourceInterface $resource The resource to publish
	 * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or the resource could not be published for other reasons
	 */
	public function getResourceWebUri(ResourceInterface $resource) {
		return $this->publishResource($resource);
	}

	/**
	 * @param ResourceInterface $resource
	 * @return string
	 */
	protected function getResourceUri(ResourceInterface $resource) {
		return PathUtility::getCanonicalPath($this->getResourcesBaseUri() . '/' . $resource->getIdentifier());
	}

	/**
	 * Returns the base URI where persistent resources are published an accessible from the outside.
	 *
	 * @return string The base URI
	 */
	public function getResourcesBaseUri() {
		if ($this->resourcesBaseUri === NULL) {
			$this->detectResourcesBaseUri();
		}
		return $this->resourcesBaseUri;
	}

	/**
	 * @param string $resourceSourcePath Absolute path to resources
	 */
	public function setResourcesSourcePath($resourceSourcePath) {
		$this->resourcesSourcePath = $resourceSourcePath;
		$this->detectResourcesPublishingPath();
	}

	/**
	 * Sets the URI of resources by removing the absolute path to the document root from the absolute publishing path
	 */
	protected function detectResourcesBaseUri() {
		$this->resourcesBaseUri = substr($this->resourcesPublishingPath, strlen(PATH_site));
	}

	/**
	 * Sets the publishing path depending on the resources path being in document root or not
	 */
	protected function detectResourcesPublishingPath() {
		if ($this->resourcesPublishingPath === NULL) {
			if ($this->isSourcePathInDocumentRoot()) {
				$this->resourcesPublishingPath = $this->resourcesSourcePath;
			} else {
				// TODO: handle this case
			}
		}
	}

	/**
	 * Checks if the source path is somewhere below the document root
	 *
	 * @return bool
	 */
	protected function isSourcePathInDocumentRoot() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($this->resourcesSourcePath, PATH_site);
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
}