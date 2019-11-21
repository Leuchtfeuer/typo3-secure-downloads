<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\Resource\Publishing;

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
use Bitmotion\SecureDownloads\Request\RequestContext;
use TYPO3\CMS\Core\Core\Environment;
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

    /**
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

    public function __construct()
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
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

    protected function getPathSite(): string
    {
        return Environment::getPublicPath() . '/';
    }

    /**
     * Sets the URI of resources by removing the absolute path to the document root from the absolute publishing path
     */
    protected function detectResourcesBaseUri(): void
    {
        $this->resourcesBaseUri = substr($this->resourcesPublishingPath, strlen($this->getPathSite()));
    }

    protected function getResourcesSourcePathByResourceStorage(ResourceStorage $storage): string
    {
        $storageConfiguration = $storage->getConfiguration();
        if ($storageConfiguration['pathType'] === 'absolute') {
            $sourcePath = PathUtility::getCanonicalPath($storageConfiguration['basePath']) . '/';
        } else {
            $sourcePath = PathUtility::getCanonicalPath($this->getPathSite() . $storageConfiguration['basePath']) . '/';
        }

        return $sourcePath;
    }

    /**
     * @param string $resourceSourcePath Absolute path to resources
     */
    protected function setResourcesSourcePath(string $resourceSourcePath): void
    {
        $this->resourcesSourcePath = $resourceSourcePath;
        $this->detectResourcesPublishingPath();
    }

    /**
     * Sets the publishing path depending on the resources path being in document root or not
     */
    protected function detectResourcesPublishingPath(): void
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
        return GeneralUtility::isFirstPartOfStr($this->resourcesSourcePath, $this->getPathSite());
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
    protected function buildRequestContext(): void
    {
        $this->requestContext = new RequestContext();
    }
}
