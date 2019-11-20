<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\Request;

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

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
    protected $cookieName = 'secure_download_token';

    /**
     * @var string
     */
    protected $additionalSecret = 'secure_download_token';

    /**
     * @var AbstractUserAuthentication
     */
    protected $currentUser;

    /**
     * @var string
     */
    protected $locationId;

    public function __construct()
    {
        if ($this->isFrontendRequest()) {
            $this->initializeFrontendContext();
        } elseif ($this->isBackendRequest()) {
            // TODO: Reintroduce this feature?
        } else {
            throw new \LogicException('Unknown Context.', 1377180593);
        }
    }

    public function isFrontendRequest(): bool
    {
        if (defined('TYPO3_MODE') && TYPO3_MODE === 'FE') {
            return true;
        }

        return false;
    }

    /**
     * Initializes the request context, when called from a frontend request
     */
    protected function initializeFrontendContext(): void
    {
        $typoScriptFrontendController = $GLOBALS['TSFE'];

        if (isset($typoScriptFrontendController->page['cache_timeout']) && $typoScriptFrontendController->page['cache_timeout'] > 0) {
            $this->cacheLifetime = (int)$typoScriptFrontendController->page['cache_timeout'];
        } elseif (isset($typoScriptFrontendController->config['config']['cache_period'])) {
            $this->cacheLifetime = (int)$typoScriptFrontendController->config['config']['cache_period'];
        } else {
            $this->cacheLifetime = 0;
        }

        $this->currentUser = $typoScriptFrontendController->fe_user;

        if ($this->isUserLoggedIn()) {
            $this->userId = (int)$this->currentUser->user['uid'];
            $this->userGroupIds = array_unique(array_map('intval', $this->currentUser->groupData['uid']));
            sort($this->userGroupIds);

            // TODO: $typoScriptFrontendController->config is deprecated since TYPO3 9.0
            if (isset($typoScriptFrontendController->config['tx_securedownloads']['additionalSecret'])) {
                $this->additionalSecret = $typoScriptFrontendController->config['tx_securedownloads']['additionalSecret'];
            } else {
                $this->setAdditionalSecret();
                $typoScriptFrontendController->config['tx_securedownloads']['additionalSecret'] = $this->additionalSecret;
            }
        } else {
            $this->setAdditionalSecret();
        }

        if (isset($typoScriptFrontendController->config['config']['tx_securedownloads_enable']) && $typoScriptFrontendController->config['config']['tx_securedownloads_enable'] === '0') {
            $this->urlRewritingEnabled = false;
        }

        $this->locationId = (string)$typoScriptFrontendController->id;
    }

    public function isUserLoggedIn(): bool
    {
        if (empty($this->currentUser->user['uid'])) {
            return false;
        }

        return true;
    }

    /**
     * Use the encryptionKey as additionalSecret if defined
     */
    private function setAdditionalSecret(): void
    {
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            $this->additionalSecret = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        } else {
            $this->additionalSecret = GeneralUtility::makeInstance(Random::class)->generateRandomHexString(64);
        }
    }

    protected function isBackendRequest(): bool
    {
        if (defined('TYPO3_MODE') && TYPO3_MODE === 'BE') {
            return true;
        }

        return false;
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

    public function isUrlRewritingEnabled(): bool
    {
        return $this->urlRewritingEnabled;
    }

    public function getCookieName(): string
    {
        return $this->cookieName;
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
