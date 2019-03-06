<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\Resource\Publishing;

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
use Bitmotion\SecureDownloads\Configuration\ConfigurationManager;
use Bitmotion\SecureDownloads\Request\RequestContext;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

abstract class AbstractResourcePublishingTarget implements ResourcePublishingTargetInterface, SingletonInterface
{
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

    protected $configurationManager;

    public function __construct(ConfigurationManager $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Returns the web URI pointing to the published resource
     *
     * @param ResourceInterface $resource The resource to publish
     *
     * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or
     *     the resource could not be published for other reasons
     */
    public function getResourceWebUri(ResourceInterface $resource)
    {
        return $this->publishResource($resource);
    }

    protected function getResourceUri(ResourceInterface $resource): string
    {
        return PathUtility::getCanonicalPath($this->getResourcesBaseUri() . '/' . $resource->getIdentifier());
    }

    /**
     * Returns the base URI where persistent resources are published an accessible from the outside.
     *
     * @return string The base URI
     */
    public function getResourcesBaseUri(): string
    {
        if ($this->resourcesBaseUri === null) {
            $this->detectResourcesBaseUri();
        }

        return $this->resourcesBaseUri;
    }

    /**
     * Sets the URI of resources by removing the absolute path to the document root from the absolute publishing path
     */
    protected function detectResourcesBaseUri()
    {
        $this->resourcesBaseUri = substr($this->resourcesPublishingPath, strlen(PATH_site));
    }

    protected function getResourcesSourcePathByResourceStorage(ResourceStorage $storage): string
    {
        $storageConfiguration = $storage->getConfiguration();
        if ($storageConfiguration['pathType'] === 'absolute') {
            $sourcePath = PathUtility::getCanonicalPath($storageConfiguration['basePath']) . '/';
        } else {
            $sourcePath = PathUtility::getCanonicalPath(PATH_site . $storageConfiguration['basePath']) . '/';
        }

        return $sourcePath;
    }

    /**
     * @param string $resourceSourcePath Absolute path to resources
     */
    protected function setResourcesSourcePath(string $resourceSourcePath)
    {
        $this->resourcesSourcePath = $resourceSourcePath;
        $this->detectResourcesPublishingPath();
    }

    /**
     * Sets the publishing path depending on the resources path being in document root or not
     */
    protected function detectResourcesPublishingPath()
    {
        if ($this->resourcesPublishingPath === null) {
            if ($this->isSourcePathInDocumentRoot()) {
                $this->resourcesPublishingPath = $this->resourcesSourcePath;
            }
            // TODO: handle this case
        }
    }

    /**
     * Checks if the source path is somewhere below the document root
     */
    protected function isSourcePathInDocumentRoot(): bool
    {
        return GeneralUtility::isFirstPartOfStr($this->resourcesSourcePath, PATH_site);
    }

    protected function getRequestContext(): RequestContext
    {
        if ($this->requestContext === null) {
            $this->buildRequestContext();
        }

        return $this->requestContext;
    }

    /**
     * Creates the request context
     */
    protected function buildRequestContext()
    {
        $this->requestContext = new RequestContext();
    }
}
