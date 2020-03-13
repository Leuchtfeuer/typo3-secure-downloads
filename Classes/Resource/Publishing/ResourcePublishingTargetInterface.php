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

/**
 * @deprecated Will be removed in version 5.
 */
interface ResourcePublishingTargetInterface
{
    /**
     * Returns the web URI pointing to the published resource
     *
     * @param ResourceInterface $resource The resource to publish
     *
     * @return string Either the web URI of the published resource or FALSE if the resource source file doesn't exist
     *     or the resource could not be published for other reasons
     */
    public function getResourceWebUri(ResourceInterface $resource);

    /**
     * Publishes a persistent resource to the web accessible resources directory
     *
     * @param ResourceInterface $resource The resource to publish
     *
     * @return string Either the web URI of the published resource or FALSE if the resource source file doesn't exist
     *     or the resource could not be published for other reasons
     */
    public function publishResource(ResourceInterface $resource);

    /**
     * Builds a delivery URI from a URI which is in document root but protected through the webserver
     */
    public function publishResourceUri(string $resourceUri): string;
}
