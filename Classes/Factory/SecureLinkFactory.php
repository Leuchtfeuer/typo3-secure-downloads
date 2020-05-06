<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Factory;

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

use Firebase\JWT\JWT;
use Leuchtfeuer\SecureDownloads\Cache\EncodeCache;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Download;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\SecureDownloads\Resource\Event\EnrichPayloadEvent;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class SecureLinkFactory implements SingletonInterface
{
    const DEFAULT_CACHE_LIFETIME = 86400;

    private $eventDispatcher;

    private $extensionConfiguration;

    private $download;

    /**
     * @deprecated
     */
    protected $userId = 0;

    /**
     * @deprecated
     */
    protected $userGroups = [];

    /**
     * @deprecated
     */
    protected $pageId = 0;

    /**
     * @deprecated
     */
    protected $resourceUri = '';

    /**
     * @deprecated
     */
    protected $linkTimeout = 0;

    public function __construct(EventDispatcher $eventDispatcher, ExtensionConfiguration $extensionConfiguration)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->extensionConfiguration = $extensionConfiguration;
        $this->download = new Download();
        $this->init();
    }

    public function setResourceUri(string $resourceUri): void
    {
        $this->download->setFile($resourceUri);
    }

    /**
     * Builds a URI which uses a PHP Script to access the resource by taking several parameters into account.
     */
    public function getUrl(): string
    {
        $resourceUri = $this->download->getFile();
        $hash = md5($this->download->getUser() . $this->download->getGroups() . $resourceUri . $this->download->getPage());

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

    protected function init()
    {
        $this->download->setExp($this->calculateLinkLifetime());
        $this->download->setPage((int)$GLOBALS['TSFE']->id);
        $this->download->setIat(time());

        try {
            /** @var UserAspect $userAspect */
            $userAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');
            $this->download->setUser($userAspect->get('id'));
            $this->download->setGroups($userAspect->getGroupIds());
        } catch (\Exception $exception) {
            // Do nothing.
        }
    }

    protected function getJsonWebToken(): string
    {
        $payload = [
            'iat' => $this->download->getIat(),
            'exp' => $this->download->getExp(),
            'user' => $this->download->getUser(),
            'groups' => $this->download->getGroups(),
            'file' => $this->download->getFile(),
            'page' => $this->download->getPage(),
        ];

        $this->dispatchEnrichPayloadEvent($payload);

        return JWT::encode($payload, $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'], 'HS256');
    }

    protected function dispatchEnrichPayloadEvent(array &$payload): void
    {
        $event = new EnrichPayloadEvent($payload, $this->download);
        $event = $this->eventDispatcher->dispatch($event);
        $payload = $event->getPayload();
    }

    protected function calculateLinkLifetime(): int
    {
        $cacheTimeout = ($GLOBALS['TSFE'] instanceof TypoScriptFrontendController && !empty($GLOBALS['TSFE']->page)) ? $GLOBALS['TSFE']->get_cache_timeout() : self::DEFAULT_CACHE_LIFETIME;

        return $cacheTimeout + $GLOBALS['EXEC_TIME'] + $this->extensionConfiguration->getCacheTimeAdd();
    }

    /**
     * @deprecated Will be removed with version 6.
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @deprecated Will be removed with version 6.
     */
    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @deprecated Will be removed with version 6.
     */
    public function getUserGroups(): array
    {
        return $this->userGroups;
    }

    /**
     * @deprecated Will be removed with version 6.
     */
    public function setUserGroups(array $userGroups): void
    {
        $this->userGroups = $userGroups;
    }

    /**
     * @deprecated Will be removed with version 6.
     */
    public function getPageId(): int
    {
        return $this->pageId;
    }

    /**
     * @deprecated Will be removed with version 6.
     */
    public function setPageId(int $pageId): void
    {
        $this->pageId = $pageId;
    }

    /**
     * @deprecated Will be removed with version 6.
     */
    public function getResourceUri(): string
    {
        return $this->resourceUri;
    }

    /**
     * @deprecated Will be removed with version 6.
     */
    public function getLinkTimeout(): int
    {
        return $this->linkTimeout;
    }

    /**
     * @deprecated Will be removed with version 6.
     */
    public function setLinkTimeout(int $linkTimeout): void
    {
        $this->linkTimeout = $linkTimeout;
    }
}
