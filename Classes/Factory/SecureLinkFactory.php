<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Factory;

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

use Bitmotion\SecureDownloads\Cache\EncodeCache;
use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Bitmotion\SecureDownloads\Utility\HookUtility;
use Firebase\JWT\JWT;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class SecureLinkFactory
{
    const DEFAULT_CACHE_LIFETIME = 86400;

    private $extensionConfiguration;

    protected $userId = 0;

    protected $userGroups = [];

    protected $pageId = 0;

    protected $resourceUri = '';

    protected $linkTimeout = 0;

    public function __construct(string $resourceUri)
    {
        $this->extensionConfiguration = new ExtensionConfiguration();
        $this->setResourceUri($resourceUri);
        $this->setLinkTimeout($this->calculateLinkLifetime());

        try {
            /** @var UserAspect $userAspect */
            $userAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');
            $this->setUserId($userAspect->get('id'));
            $this->setUserGroups($userAspect->getGroupIds());
            $this->setPageId((int)$GLOBALS['TSFE']->id);
        } catch (\Exception $exception) {
            // Do nothing.
        }
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getUserGroups(): array
    {
        return $this->userGroups;
    }

    public function setUserGroups(array $userGroups): void
    {
        $this->userGroups = $userGroups;
    }

    public function getPageId(): int
    {
        return $this->pageId;
    }

    public function setPageId(int $pageId): void
    {
        $this->pageId = $pageId;
    }

    public function getResourceUri(): string
    {
        return $this->resourceUri;
    }

    public function setResourceUri(string $resourceUri): void
    {
        $this->resourceUri = $resourceUri;
    }

    public function getLinkTimeout(): int
    {
        return $this->linkTimeout;
    }

    public function setLinkTimeout(int $linkTimeout): void
    {
        $this->linkTimeout = $linkTimeout;
    }

    /**
     * Builds a URI which uses a PHP Script to access the resource by taking several parameters into account.
     */
    public function getUrl(): string
    {
        $userId = $this->getUserId();
        $userGroups = $this->getUserGroups();
        $pageId = $this->getPageId();
        $resourceUri = $this->getResourceUri();

        $hash = md5($userId . $userGroups . $resourceUri . $pageId);

        // Retrieve URL from JWT cache
        if (EncodeCache::hasCache($hash)) {
            return EncodeCache::getCache($hash);
        }

        $url = sprintf(
            '%s/%s%s/%s',
            $this->extensionConfiguration->getLinkPrefix(),
            $this->extensionConfiguration->getTokenPrefix(),
            $this->getJsonWebToken(),
            pathinfo($resourceUri, PATHINFO_BASENAME)
        );

        // Store URL in JWT cache
        EncodeCache::addCache($hash, $url);

        return $url;
    }

    protected function getJsonWebToken(): string
    {
        $payload = [
            'iat' => time(),
            'exp' => $this->getLinkTimeout(),
            'user' => $this->getUserId(),
            'groups' => $this->getUserGroups(),
            'file' => $this->getResourceUri(),
            'page' => $this->getPageId(),
        ];

        // Execute hook for manipulating payload
        HookUtility::executeHook('publishing', 'payload', $payload, $this);

        return JWT::encode($payload, $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'], 'HS256');
    }

    protected function calculateLinkLifetime(): int
    {
        // TODO: TSFE should always be available when dropping HTML parsing.
        $cacheTimeout = ($GLOBALS['TSFE'] instanceof TypoScriptFrontendController && !empty($GLOBALS['TSFE']->page)) ? $GLOBALS['TSFE']->get_cache_timeout() : self::DEFAULT_CACHE_LIFETIME;

        return $cacheTimeout + $GLOBALS['EXEC_TIME'] + $this->extensionConfiguration->getCacheTimeAdd();
    }
}
