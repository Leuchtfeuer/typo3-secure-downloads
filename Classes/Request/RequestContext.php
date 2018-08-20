<?php
namespace Bitmotion\SecureDownloads\Request;

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
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class RequestContext
 * @package Bitmotion\SecureDownloads\Request
 */
class RequestContext
{
    /**
     * @var int
     */
    protected $userId = 0;

    /**
     * @var array<int>
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

    /**
     * RequestContext constructor.
     */
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

    /**
     * @return bool
     */
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
    protected function initializeFrontendContext()
    {
        /** @var TypoScriptFrontendController $typoScriptFrontendController */
        $typoScriptFrontendController = $GLOBALS['TSFE'];

        if (isset($typoScriptFrontendController->page['cache_timeout'])) {
            $this->cacheLifetime = (int)$typoScriptFrontendController->page['cache_timeout'];
        } else {
            $this->cacheLifetime = 0;
        }

        $this->currentUser = $typoScriptFrontendController->fe_user;

        if ($this->isUserLoggedIn()) {
            $this->userId = (int)$this->currentUser->user['uid'];
            $this->userGroupIds = array_unique(array_map('intval', $this->currentUser->groupData['uid']));
            sort($this->userGroupIds);

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

    /**
     * @return bool
     */
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
    private function setAdditionalSecret()
    {
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
            $this->additionalSecret = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
        } else {
            $this->additionalSecret = GeneralUtility::makeInstance(Random::class)->generateRandomHexString(64);
        }
    }

    /**
     * @return bool
     */
    protected function isBackendRequest(): bool
    {
        if (defined('TYPO3_MODE') && TYPO3_MODE === 'BE') {
            return true;
        }

        return false;
    }

    /**
     * @return string
     */
    public function getAdditionalSecret(): string
    {
        return $this->additionalSecret;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return int
     */
    public function getCacheLifetime(): int
    {
        return $this->cacheLifetime;
    }

    /**
     * @return bool
     */
    public function isUrlRewritingEnabled(): bool
    {
        return $this->urlRewritingEnabled;
    }

    /**
     * @return string
     */
    public function getCookieName(): string
    {
        return $this->cookieName;
    }

    /**
     * @return string
     */
    public function getAccessToken(): string
    {
        return GeneralUtility::hmac(implode(',', $this->getUserGroupIds()), $this->additionalSecret);
    }

    /**
     * @return array
     */
    public function getUserGroupIds(): array
    {
        return $this->userGroupIds;
    }

    /**
     * @return string
     */
    public function getLocationId(): string
    {
        return $this->locationId;
    }
}