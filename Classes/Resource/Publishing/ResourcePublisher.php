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
use Bitmotion\SecureDownloads\Security\Authorization\Resource\Apache2AccessRestrictionPublisher;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

class ResourcePublisher implements SingletonInterface
{
    /**
     * @var ResourcePublisher
     */
    protected $publishingTarget;

    public function injectPublishingTarget(ResourcePublishingTargetInterface $publishingTarget): void
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

    protected function getPublishingTarget(): ResourcePublishingTargetInterface
    {
        // Check if we have DI, if not, lazily instatiate the publishing target
        if (is_null($this->publishingTarget)) {
            $this->publishingTarget = GeneralUtility::makeInstance(ObjectManager::class)->get(ResourcePublishingTargetInterface::class);
            if (method_exists($this->publishingTarget, 'injectConfigurationManager')) {
                $this->publishingTarget->injectConfigurationManager(GeneralUtility::makeInstance(ConfigurationManager::class));
            }
            if (method_exists($this->publishingTarget, 'injectAccessRestrictionPublisher')) {
                $this->publishingTarget->injectAccessRestrictionPublisher(GeneralUtility::makeInstance(Apache2AccessRestrictionPublisher::class));
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
    public function publishResource(ResourceInterface $resource): void
    {
        return $this->getPublishingTarget()->publishResource($resource);
    }

    /**
     * Builds a delivery URI from a URI which is in document root but protected through the webserver
     */
    public function publishResourceUri(string $resourceUri): string
    {
        return $this->getPublishingTarget()->publishResourceUri($resourceUri);
    }
}
