<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Request;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * @deprecated Will be removed in version 5.
 */
class RequestContext
{
    /**
     * @var int
     */
    protected $userId = 0;

    /**
     * @var int[]
     */
    protected $userGroupIds = [0];

    /**
     * @var int
     */
    protected $cacheLifetime = 0;

    /**
     * @var bool
     */
    protected $urlRewritingEnabled = true;

    /**
     * @var string
     */
    protected $additionalSecret = 'secure_download_token';

    /**
     * @var FrontendUserAuthentication
     */
    protected $frontendUserAuthentication;

    /**
     * @var string
     */
    protected $locationId;

    public function __construct()
    {
        $environmentService = GeneralUtility::makeInstance(EnvironmentService::class);

        if ($environmentService->isEnvironmentInFrontendMode()) {
            $this->initializeFrontendContext();
        } elseif ($environmentService->isEnvironmentInBackendMode()) {
            // Skip
        } elseif (Environment::isCli()) {
            // Skip
        } else {
            throw new \LogicException('Unknown Context.', 1377180593);
        }
    }

    /**
     * @deprecated Will be removed in version 5. Use EnvironmentService instead.
     */
    public function isFrontendRequest(): bool
    {
        $environmentService = GeneralUtility::makeInstance(EnvironmentService::class);

        return $environmentService->isEnvironmentInFrontendMode();
    }

    /**
     * Initializes the request context, when called from a frontend request
     */
    protected function initializeFrontendContext(): void
    {
        $this->setCacheLifetime();
        $this->frontendUserAuthentication = $GLOBALS['TSFE']->fe_user;

        if ($this->isUserLoggedIn()) {
            $this->userId = (int)$this->frontendUserAuthentication->user['uid'];
            $this->userGroupIds = array_unique(array_map('intval', $this->frontendUserAuthentication->groupData['uid']));
            sort($this->userGroupIds);

            // TODO: $typoScriptFrontendController->config is deprecated since TYPO3 9.0
            if (isset($GLOBALS['TSFE']->config['tx_securedownloads']['additionalSecret'])) {
                $this->additionalSecret = $GLOBALS['TSFE']->config['tx_securedownloads']['additionalSecret'];
            } else {
                $this->setAdditionalSecret();
                $GLOBALS['TSFE']->config['tx_securedownloads']['additionalSecret'] = $this->additionalSecret;
            }
        } else {
            $this->setAdditionalSecret();
        }

        if (isset($GLOBALS['TSFE']->config['config']['tx_securedownloads_enable']) && $GLOBALS['TSFE']->config['config']['tx_securedownloads_enable'] === '0') {
            $this->urlRewritingEnabled = false;
        }

        $this->locationId = (string)$GLOBALS['TSFE']->id;
    }

    public function isUserLoggedIn(): bool
    {
        return !empty($this->frontendUserAuthentication->user['uid']);
    }

    /**
     * Use the encryptionKey as additionalSecret if defined
     */
    private function setAdditionalSecret(): void
    {
        $this->additionalSecret = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] ?? GeneralUtility::makeInstance(Random::class)->generateRandomHexString(64);
    }

    public function getAdditionalSecret(): string
    {
        return $this->additionalSecret;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function getCacheLifetime(): int
    {
        return $this->cacheLifetime;
    }

    public function setCacheLifetime(): void
    {
        if (isset($GLOBALS['TSFE']->page['cache_timeout']) && $GLOBALS['TSFE']->page['cache_timeout'] > 0) {
            $this->cacheLifetime = (int)$GLOBALS['TSFE']->page['cache_timeout'];
        } elseif (isset($GLOBALS['TSFE']->config['config']['cache_period'])) {
            $this->cacheLifetime = (int)$GLOBALS['TSFE']->config['config']['cache_period'];
        } else {
            $this->cacheLifetime = 0;
        }
    }

    public function isUrlRewritingEnabled(): bool
    {
        return $this->urlRewritingEnabled;
    }

    public function getAccessToken(): string
    {
        return GeneralUtility::hmac(implode(',', $this->getUserGroupIds()), $this->additionalSecret);
    }

    public function getUserGroupIds(): array
    {
        return $this->userGroupIds;
    }

    public function getLocationId(): string
    {
        return $this->locationId;
    }
}
