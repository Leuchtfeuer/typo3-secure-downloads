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

use Bitmotion\SecureDownloads\Cache\EncodeCache;
use Bitmotion\SecureDownloads\Parser\HtmlParser;
use Bitmotion\SecureDownloads\Utility\HookUtility;
use Firebase\JWT\JWT;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class PhpDeliveryProtectedResourcePublishingTarget extends AbstractResourcePublishingTarget
{
    const DEFAULT_CACHE_LIFETIME = 86400;

    /**
     * Builds a URI which uses a PHP Script to access the resource
     */
    public function publishResourceUri(string $resourceUri): string
    {
        $this->setResourcesSourcePath(Environment::getPublicPath() . '/');

        return $this->buildUri($resourceUri);
    }

    /**
     * Publishes a persistent resource to the web accessible resources directory
     *
     * @param ResourceInterface $resource The resource to publish
     *
     * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or
     *     the resource could not be published for other reasons
     *
     * @deprecated Will be removed in version 5.
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
     *
     * @deprecated Will be removed in version 5. Use the SecureDownloadService instead.
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

    /**
     * Builds a URI which uses a PHP Script to access the resource
     * by taking several parameters into account
     */
    protected function buildUri(string $resourceUri): string
    {
        /** @var UserAspect $userAspect */
        $userAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');
        $user = $userAspect->get('id');
        $userGroups = $userAspect->getGroupIds();

        $hash = md5($user . $userGroups . $resourceUri . $GLOBALS['TSFE']->id);

        // Retrieve URL from JWT cache
        if (EncodeCache::hasCache($hash)) {
            return EncodeCache::getCache($hash);
        }

        $url = sprintf(
            '%s/%s%s/%s',
            $this->extensionConfiguration->getLinkPrefix(),
            $this->extensionConfiguration->getTokenPrefix(),
            $this->getJsonWebToken($user, $userGroups, $resourceUri),
            pathinfo($resourceUri, PATHINFO_BASENAME)
        );

        // Store URL in JWT cache
        EncodeCache::addCache($hash, $url);

        return $url;
    }

    private function getJsonWebToken(int $user, array $userGroups, string $resourceUri): string
    {
        $payload = [
            'iat' => time(),
            'exp' => $this->calculateLinkLifetime(),
            'user' => $user,
            'groups' => $userGroups,
            'file' => $resourceUri,
            'page' => $GLOBALS['TSFE']->id,
        ];

        // Execute hook for manipulating payload
        HookUtility::executeHook('publishing', 'payload', $payload, $this);

        return JWT::encode($payload, $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'], 'HS256');
    }

    protected function calculateLinkLifetime(): int
    {
        // TODO: TSFE should always be available when dropping HTML parsing.
        $cacheTimeout = ($GLOBALS['TSFE'] instanceof TypoScriptFrontendController && is_array($GLOBALS['TSFE']->page)) ? $GLOBALS['TSFE']->get_cache_timeout() : self::DEFAULT_CACHE_LIFETIME;

        return $cacheTimeout + $GLOBALS['EXEC_TIME'] + $this->extensionConfiguration->getCacheTimeAdd();
    }
}
