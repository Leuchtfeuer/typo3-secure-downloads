<?php
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
use Bitmotion\SecureDownloads\Security\Authorization\Resource\AccessRestrictionPublisherInterface;
use Bitmotion\SecureDownloads\Security\Authorization\Resource\Apache2AccessRestrictionPublisher;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class ResourcePublisher
 * @package Bitmotion\SecureDownloads\Resource\Publishing
 */
class ResourcePublisher implements SingletonInterface
{
    /**
     * @var ResourcePublishingTargetInterface
     */
    protected $publishingTarget;

    /**
     * @param ResourcePublishingTargetInterface $publishingTarget
     */
    public function injectPublishingTarget(ResourcePublishingTargetInterface $publishingTarget)
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
     */
    public function getResourceWebUri(ResourceInterface $resource)
    {
        return $this->getPublishingTarget()->getResourceWebUri($resource);
    }

    /**
     * @return ResourcePublishingTargetInterface
     */
    protected function getPublishingTarget()
    {
        // Check if we have DI, if not, lazily instatiate the publishing target
        if (is_null($this->publishingTarget)) {
            $this->publishingTarget = GeneralUtility::makeInstance('Bitmotion\\SecureDownloads\\Resource\\Publishing\\ResourcePublisher');
            if (method_exists($this->publishingTarget, 'injectConfigurationManager')) {
                $this->publishingTarget->injectConfigurationManager(GeneralUtility::makeInstance('Bitmotion\\SecureDownloads\\Configuration\\ConfigurationManager'));
            }
            if (method_exists($this->publishingTarget, 'injectAccessRestrictionPublisher')) {
                $this->publishingTarget->injectAccessRestrictionPublisher(GeneralUtility::makeInstance('Bitmotion\\SecureDownloads\\Resource\\Publishing\\AccessRestrictionPublisherInterface'));
            }
        }

        return $this->publishingTarget;
    }

    /**
     * Publishes a persistent resource to the web accessible resources directory
     *
     * @param ResourceInterface $resource The resource to publish
     *
     * @return string Either the web URI of the published resource or FALSE if the resource source file doesn't exist
     *     or the resource could not be published for other reasons
     */
    public function publishResource(ResourceInterface $resource)
    {
        return $this->getPublishingTarget()->publishResource($resource);
    }

    /**
     * Builds a delivery URI from a URI which is in document root but protected through the webserver
     *
     * @param $resourceUri
     *
     * @return string
     */
    public function publishResourceUri($resourceUri)
    {
        return $this->getPublishingTarget()->publishResourceUri($resourceUri);
    }
}
