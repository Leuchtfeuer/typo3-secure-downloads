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

use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * @deprecated Will be removed in version 5.
 */
class ResourcePublisher implements SingletonInterface
{
    /**
     * @var ResourcePublisher
     */
    protected $publishingTarget;

    public function __construct(PhpDeliveryProtectedResourcePublishingTarget $publishingTarget)
    {
        $this->publishingTarget = $publishingTarget;
    }

    /**
     * Returns the web URI pointing to the published resource
     *
     * @param ResourceInterface $resource The resource to publish
     *
     * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or
     *     the resource could not be published for other reasons
     *
     * @deprecated Will be removed in version 5.
     */
    public function getResourceWebUri(ResourceInterface $resource)
    {
        trigger_error('Method getResourceWebUri() will be removed in version 5.', E_USER_DEPRECATED);

        return $this->publishingTarget->getResourceWebUri($resource);
    }

    /**
     * Publishes a persistent resource to the web accessible resources directory
     *
     * @param ResourceInterface $resource The resource to publish
     *
     * @return string Either the web URI of the published resource or FALSE if the resource source file doesn't exist
     *     or the resource could not be published for other reasons
     *
     * @deprecated Will be removed in version 5.
     */
    public function publishResource(ResourceInterface $resource)
    {
        trigger_error('Method publishResource() will be removed in version 5.', E_USER_DEPRECATED);

        return $this->publishingTarget->publishResource($resource);
    }

    /**
     * Builds a delivery URI from a URI which is in document root but protected through the webserver
     *
     * @deprecated Will be removed in version 5. Use SecureLinkFactory->getUrl() instead.
     */
    public function publishResourceUri(string $resourceUri): string
    {
        return $this->publishingTarget->publishResourceUri($resourceUri);
    }
}
