<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Resource\Publishing;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Bitmotion\SecureDownloads\Request\RequestContext;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * @deprecated Will be removed in version 5.
 */
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
        trigger_error('Method getResourceWebUri() will be removed in version 5.', E_USER_DEPRECATED);

        return $this->publishResource($resource);
    }

    protected function getResourceUri(ResourceInterface $resource): string
    {
        trigger_error('Method getResourceUri() will be removed in version 5.', E_USER_DEPRECATED);

        return PathUtility::getCanonicalPath($this->getResourcesBaseUri() . '/' . $resource->getIdentifier());
    }

    /**
     * Returns the base URI where persistent resources are published an accessible from the outside.
     *
     * @return string The base URI
     */
    public function getResourcesBaseUri(): string
    {
        trigger_error('Method getResourcesBaseUri() will be removed in version 5.', E_USER_DEPRECATED);

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
        trigger_error('Method detectResourcesBaseUri() will be removed in version 5.', E_USER_DEPRECATED);

        $this->resourcesBaseUri = substr($this->resourcesPublishingPath, strlen($this->getPathSite()));
    }

    protected function getResourcesSourcePathByResourceStorage(ResourceStorage $storage): string
    {
        trigger_error('Method getResourcesSourcePathByResourceStorage() will be removed in version 5.', E_USER_DEPRECATED);

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
