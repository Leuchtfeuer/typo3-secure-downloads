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

use Bitmotion\SecureDownloads\Factory\SecureLinkFactory;
use Bitmotion\SecureDownloads\Parser\HtmlParser;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

/**
 * @deprecated Will be removed in version 5.
 */
class PhpDeliveryProtectedResourcePublishingTarget extends AbstractResourcePublishingTarget
{
    /**
     * Builds a URI which uses a PHP Script to access the resource
     */
    public function publishResourceUri(string $resourceUri): string
    {
        $secureLinkFactory = GeneralUtility::makeInstance(SecureLinkFactory::class, $resourceUri);

        return $secureLinkFactory->getUrl();
    }

    /**
     * Publishes a persistent resource to the web accessible resources directory
     *
     * @param ResourceInterface $resource The resource to publish
     *
     * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or
     *     the resource could not be published for other reasons
     */
    public function publishResource(ResourceInterface $resource)
    {
        trigger_error('Method publishResource() will be removed in version 5.', E_USER_DEPRECATED);

        $publicUrl = false;
        $environmentService = GeneralUtility::makeInstance(EnvironmentService::class);

        // We only manipulate the URL if we are in the backend or in FAL mode in FE (otherwise we parse the HTML)
        if (!$environmentService->isEnvironmentInFrontendMode()) {
            $this->setResourcesSourcePath($this->getResourcesSourcePathByResourceStorage($resource->getStorage()));
            if ($this->isSourcePathInDocumentRoot()) {
                // We need to use absolute paths then or copy the files around, or...
                if (!$this->isPubliclyAvailable($resource)) {
                    $publicUrl = $this->buildUri($this->getResourceUri($resource));
                }
            }
        }

        return $publicUrl;
    }

    /**
     * Checks if a resource which lies in document root is really publicly available
     * This is currently only done by checking configured secure paths, not by requesting the resources
     */
    protected function isPubliclyAvailable(ResourceInterface $resource): bool
    {
        trigger_error('Method isPubliclyAvailable() will be removed in version 5.', E_USER_DEPRECATED);

        $resourceUri = $this->getResourceUri($resource);
        $securedFoldersExpression = $this->extensionConfiguration->getSecuredDirs();
        $securedFileTypes = $this->extensionConfiguration->getSecuredFileTypes();

        if (substr($securedFileTypes, 0, 1) === '\\') {
            $fileExtensionExpression = $securedFileTypes;
        } else {
            $fileExtensionExpression = '\\.(' . $securedFileTypes . ')';
        }

        // TODO: maybe check if the resource is available without authentication by doing a head request
        return !(preg_match(
            '/((' . HtmlParser::softQuoteExpression($securedFoldersExpression) . ')+?\/.*?(?:(?i)' . ($fileExtensionExpression) . '))/i',
            $resourceUri,
            $matchedUrls
        ) && is_array($matchedUrls) && $matchedUrls[0] === $resourceUri);
    }
}
