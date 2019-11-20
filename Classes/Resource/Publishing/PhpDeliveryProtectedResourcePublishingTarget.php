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

use Bitmotion\SecureDownloads\Parser\HtmlParser;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PhpDeliveryProtectedResourcePublishingTarget extends AbstractResourcePublishingTarget
{
    const DEFAULT_LINK_FORMAT = 'index.php?eID=tx_securedownloads&p=###PAGE###&u=###FEUSER###&g=###FEGROUPS###&t=###TIMEOUT###&hash=###HASH###&file=###FILE###';

    const DEFAULT_CACHE_LIFETIME = 86400;

    const ALLOWED_TOKENS = [
        '###FEUSER###',
        '###FEGROUPS###',
        '###FILE###',
        '###TIMEOUT###',
        '###HASH###',
        '###PAGE###',
    ];

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
        $publicUrl = false;
        // We only manipulate the URL if we are in the backend or in FAL mode in FE (otherwise we parse the HTML)
        if (!$this->getRequestContext()->isFrontendRequest()) {
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
        $resourceUri = $this->getResourceUri($resource);
        $securedFoldersExpression = $this->configurationManager->getValue('securedDirs');
        if (substr($this->configurationManager->getValue('securedFiletypes'), 0, 1) === '\\') {
            $fileExtensionExpression = $this->configurationManager->getValue('securedFiletypes');
        } else {
            $fileExtensionExpression = '\\.(' . $this->configurationManager->getValue('securedFiletypes') . ')';
        }

        // TODO: maybe check if the resource is available without authentication by doing a head request
        return !(preg_match(
            '/((' . HtmlParser::softQuoteExpression($securedFoldersExpression) . ')+?\/.*?(?:(?i)' . ($fileExtensionExpression) . '))/i',
                $resourceUri,
            $matchedUrls
        ) && is_array($matchedUrls) && $matchedUrls[0] === $resourceUri);
    }

    /**
     * Builds a URI which uses a PHP Script to access the resource
     * by taking several parameters into account
     */
    protected function buildUri(string $resourceUri): string
    {
        $userId = $this->getRequestContext()->getUserId();
        $userGroupIds = $this->getRequestContext()->getUserGroupIds();
        $validityPeriod = $this->calculateLinkLifetime();
        $hash = $this->getHash($resourceUri, $userId, $userGroupIds, $validityPeriod);

        // Parsing the link format, and return this instead (an flexible link format is useful for mod_rewrite tricks ;)
        $configuredLinkFormat = $this->configurationManager->getValue('linkFormat');
        $linkFormat = !empty($configuredLinkFormat) ? $configuredLinkFormat : self::DEFAULT_LINK_FORMAT;

        $replacements = [
            $userId,
            rawurlencode(implode(',', $userGroupIds)),
            str_replace('%2F', '/', rawurlencode($resourceUri)),
            $validityPeriod,
            $hash,
            $GLOBALS['TSFE']->id,
        ];

        return str_replace(self::ALLOWED_TOKENS, $replacements, $linkFormat);
    }

    protected function calculateLinkLifetime(): int
    {
        $lifeTimeToAdd = $this->configurationManager->getValue('cachetimeadd');
        $requestCacheLifetime = $this->getRequestContext()->getCacheLifetime();
        $cacheLifetime = $requestCacheLifetime > 0 ? $requestCacheLifetime : self::DEFAULT_CACHE_LIFETIME;

        return $cacheLifetime + $GLOBALS['EXEC_TIME'] + $lifeTimeToAdd;
    }

    protected function getHash(string $resourceUri, int $userId, array $userGroupIds, int $validityPeriod): string
    {
        if ($this->configurationManager->getValue('enableGroupCheck')) {
            $hashString = $userId . implode(',', $userGroupIds) . $resourceUri . $validityPeriod;
        } else {
            $hashString = $userId . $resourceUri . $validityPeriod;
        }

        return GeneralUtility::hmac($hashString, 'bitmotion_securedownload');
    }

    /**
     * Builds a URI which uses a PHP Script to access the resource
     */
    public function publishResourceUri(string $resourceUri): string
    {
        // TODO: Remove condition when we drop TYPO3 8 LTS support
        if (class_exists('TYPO3\\CMS\\Core\\Core\\Environment')) {
            $this->setResourcesSourcePath(\TYPO3\CMS\Core\Core\Environment::getPublicPath() . '/');
        } else {
            $this->setResourcesSourcePath(PATH_site);
        }

        return $this->buildUri($resourceUri);
    }
}
