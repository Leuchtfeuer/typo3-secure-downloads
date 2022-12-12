<?php

declare(strict_types=1);
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

use Leuchtfeuer\SecureDownloads\Cache\EncodeCache;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\AbstractToken;
use Leuchtfeuer\SecureDownloads\Factory\Event\EnrichPayloadEvent;
use Leuchtfeuer\SecureDownloads\Registry\TokenRegistry;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

class SecureLinkFactory implements SingletonInterface
{
    const DEFAULT_CACHE_LIFETIME = 86400;

    /**
     * @var EventDispatcher
     */
    private $eventDispatcher;

    /**
     * @var ExtensionConfiguration
     */
    private $extensionConfiguration;

    /**
     * @var AbstractToken
     */
    private $token;

    public function __construct(EventDispatcher $eventDispatcher, ExtensionConfiguration $extensionConfiguration)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->extensionConfiguration = $extensionConfiguration;
        $this->token = TokenRegistry::getToken();
        $this->init();
    }

    /**
     * Initialize the token.
     */
    protected function init()
    {
        $this->token->setExp($this->calculateLinkLifetime());
        $this->token->setPage((int)($GLOBALS['TSFE']->id ?? 0));

        try {
            /** @var UserAspect $userAspect */
            $userAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');
            $this->token->setUser($userAspect->get('id'));
            $this->token->setGroups($userAspect->getGroupIds());
        } catch (\Exception $exception) {
            // Do nothing.
        }
    }

    /**
     * Adds the configured additional cache time and the cache lifetime of the current site to the actual time.
     *
     * @return int The link lifetime
     */
    protected function calculateLinkLifetime(): int
    {
        $cacheTimeout = (isset($GLOBALS['TSFE']) && $GLOBALS['TSFE'] instanceof TypoScriptFrontendController && !empty($GLOBALS['TSFE']->page)) ? $GLOBALS['TSFE']->get_cache_timeout() : self::DEFAULT_CACHE_LIFETIME;

        return $cacheTimeout + $GLOBALS['EXEC_TIME'] + $this->extensionConfiguration->getCacheTimeAdd();
    }

    /**
     * Builds a URI which uses a PHP Script to access the resource by taking several parameters into account.
     */
    public function getUrl(): string
    {
        $hash = $this->token->getHash();

        // Retrieve URL from JWT cache
        if (EncodeCache::hasCache($hash)) {
            return EncodeCache::getCache($hash);
        }

        $url = sprintf(
            '%s/%s%s/%s',
            $this->extensionConfiguration->getLinkPrefix(),
            $this->extensionConfiguration->getTokenPrefix(),
            $this->getJsonWebToken(),
            pathinfo($this->token->getFile(), PATHINFO_BASENAME)
        );

        // Store URL in JWT cache
        EncodeCache::addCache($hash, $url);

        return $url;
    }

    /**
     * @param int $expires The timestamp at which the link becomes invalid
     *
     * @return $this
     */
    public function withLinkTimeout(int $expires): self
    {
        $clonedObject = clone $this;
        $clonedObject->token->setExp($expires);

        return $clonedObject;
    }

    /**
     * @param int $page The page ID for which the link should be generated for
     *
     * @return $this
     */
    public function withPage(int $page): self
    {
        $clonedObject = clone $this;
        $clonedObject->token->setPage($page);

        return $clonedObject;
    }

    /**
     * @param int $user The user for which the link should be valid for
     *
     * @return $this
     */
    public function withUser(int $user): self
    {
        $clonedObject = clone $this;
        $this->token->setUser($user);

        return $clonedObject;
    }

    /**
     * @param array $groups An array of user groups for whom the link should be valid for
     *
     * @return $this
     */
    public function withGroups(array $groups): self
    {
        $clonedObject = clone $this;
        $clonedObject->token->setGroups($groups);

        return $clonedObject;
    }

    /**
     * @param string $resourceUri The actual path to the file that should be secured
     *
     * @return $this
     */
    public function withResourceUri(string $resourceUri): self
    {
        $clonedObject = clone $this;
        $clonedObject->token->setFile($resourceUri);

        return $clonedObject;
    }

    /**
     * @return string The generated JSON web token
     */
    protected function getJsonWebToken(): string
    {
        $payload = $this->token->getPayload();
        $this->dispatchEnrichPayloadEvent($payload);

        return $this->token->encode($payload);
    }

    /**
     * Dispatches the EnrichPayloadEvent event.
     *
     * @param array $payload The payload of the token
     */
    protected function dispatchEnrichPayloadEvent(array &$payload): void
    {
        $event = new EnrichPayloadEvent($payload, $this->token);
        $event = $this->eventDispatcher->dispatch($event);
        $payload = $event->getPayload();
    }
}
