<?php
namespace Bitmotion\NawSecuredl\Request;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Helmut Hummel (helmut.hummel@typo3.org)
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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Class RequestContext
 * @package Bitmotion\NawSecuredl\Request
 */
class RequestContext {
	/**
	 * @var integer
	 */
	protected $userId = 0;

	/**
	 * @var array<integer>
	 */
	protected $userGroupIds = array(0);

	/**
	 * @var integer
	 */
	protected $cacheLifetime = 0;

	/**
	 * @var bool
	 */
	protected $urlRewritingEnabled = TRUE;

	/**
	 * @var string
	 */
	protected $cookieName = 'secure_download_token';

	/**
	 * @var string
	 */
	protected $additionalSecret = 'secure_download_token';

	/**
	 * @var string
	 */
	protected $ipAddress;

	/**
	 * @var AbstractUserAuthentication
	 */
	protected $currentUser;

	/**
	 * @var string
	 */
	protected $locationId;

	public function __construct() {
		if ($this->isFrontendRequest()) {
			$this->initializeFrontendContext();
		} elseif ($this->isBackendRequest()) {
			$this->initializeBackendContext();
		} else {
			throw new \LogicException('Unknown Context.', 1377180593);
		}

	}

	/**
	 * @return string
	 */
	public function getAdditionalSecret() {
		return $this->additionalSecret;
	}

	/**
	 * @return array
	 */
	public function getUserGroupIds() {
		return $this->userGroupIds;
	}

	/**
	 * @return integer
	 */
	public function getUserId() {
		return $this->userId;
	}

	/**
	 * @return boolean
	 */
	public function isUserLoggedIn() {
		return !empty($this->currentUser->user['uid']);
	}

	/**
	 * @return integer
	 */
	public function getCacheLifetime() {
		return $this->cacheLifetime;
	}

	/**
	 * @return boolean
	 */
	public function isUrlRewritingEnabled() {
		return $this->urlRewritingEnabled;
	}

	/**
	 * @return string
	 */
	public function getCookieName() {
		return $this->cookieName;
	}

	/**
	 * @return string
	 */
	public function getIpAddress() {
		return $this->ipAddress;
	}

	public function getAccessToken() {
		//TODO: The additional secret needs to be genreated somewhere and fetched from configuration here (instead of fixed string)
		return GeneralUtility::hmac(implode(',', $this->getUserGroupIds()), $this->additionalSecret);
	}

	/**
	 * @return string
	 */
	public function getLocationId() {
		return $this->locationId;
	}

	/**
	 * Initializes the request context, when called from a frontend request
	 */
	protected function initializeFrontendContext() {
		/** @var TypoScriptFrontendController $typoScriptFrontendController */
		$typoScriptFrontendController = $GLOBALS['TSFE'];
		$this->cacheLifetime = isset($typoScriptFrontendController->page['cache_timeout']) ? (int)$typoScriptFrontendController->page['cache_timeout'] : 0;
		$this->currentUser = $typoScriptFrontendController->fe_user;
		if ($this->isUserLoggedIn()) {
			$this->userId = (int)$this->currentUser->user['uid'];
			$this->userGroupIds = array_unique(array_map('intval', $this->currentUser->groupData['uid']));
			sort($this->userGroupIds);

			if (isset($typoScriptFrontendController->config['tx_securedownload.']['additionalSecret'])) {
				$this->additionalSecret = $typoScriptFrontendController->config['tx_securedownload.']['additionalSecret'];
			} else {
				$this->additionalSecret = GeneralUtility::getRandomHexString(64);
				$typoScriptFrontendController->config['tx_securedownload.']['additionalSecret'] = $this->additionalSecret;
			}
		} else {
			$this->additionalSecret = $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
		}
		if (
			isset($typoScriptFrontendController->config['config']['tx_nawsecuredl_enable'])
			&& $typoScriptFrontendController->config['config']['tx_nawsecuredl_enable'] === '0'
		) {
			$this->urlRewritingEnabled = FALSE;
		}
		$this->locationId = (string)$typoScriptFrontendController->id;
		$this->ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : NULL;
	}

	/**
	 * Initializes the request context, when called from a backend request
	 */
	protected function initializeBackendContext() {
// Disabled for now, because we do only have php delivery script which is called in a frontend context (eID)
// If we switch to checkDataSubmission Hook for file delivery, we might activate this again
// TODO: decouple and refactor PHP delivery
//		$this->currentUser = $GLOBALS['BE_USER'];
//		if (!empty($this->currentUser->user['uid'])) {
//			$this->userId = (int)$this->currentUser->user['uid'];
//		}
//		if (!empty($this->currentUser->user['usergroup'])) {
//			$this->userGroupIds = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->currentUser->user['usergroup'], TRUE);
//		}
		$this->ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : NULL;
	}

	/**
	 * @return boolean
	 */
	public function isFrontendRequest() {
		return defined('TYPO3_MODE') && TYPO3_MODE === 'FE';
	}

	/**
	 * @return boolean
	 */
	protected function isBackendRequest() {
		return defined('TYPO3_MODE') && TYPO3_MODE === 'BE';
	}
}