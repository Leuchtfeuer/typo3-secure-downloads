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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
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
	protected $cookieName;

	/**
	 * @var string
	 */
	protected $sessionId;

	/**
	 * @var string
	 */
	protected $ipAddress;

	/**
	 * @var AbstractUserAuthentication
	 */
	protected $currentUser;

	public function __construct() {
		if ($this->isFrontendRequest()) {
			$this->initializeFrontendContext();
		} elseif ($this->isBackendRequest()) {
			$this->initializeBackendContext();
		} else {
			throw new \LogicException('Unkown Context.', 1377180593);
		}
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
	public function getSessionId() {
		return $this->sessionId;
	}

	/**
	 * @return string
	 */
	public function getIpAddress() {
		return $this->ipAddress;
	}

	/**
	 * Initializes the request context, when called from a frontend request
	 */
	protected function initializeFrontendContext() {
		/** @var TypoScriptFrontendController $typoScriptFrontendController */
		$typoScriptFrontendController = $GLOBALS['TSFE'];
		$this->cacheLifetime = (int)$typoScriptFrontendController->page['cache_timeout'];
		$this->currentUser = $typoScriptFrontendController->fe_user;
		if (isset($this->currentUser->user['uid'])) {
			$this->userId = (int)$this->currentUser->user['uid'];
			$this->userGroupIds = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->currentUser->user['usergroup'], TRUE);
		}
		if (
			isset($typoScriptFrontendController->config['config']['tx_nawsecuredl_enable'])
			&& $typoScriptFrontendController->config['config']['tx_nawsecuredl_enable'] === '0'
		) {
			$this->urlRewritingEnabled = FALSE;
		}
		$this->cookieName = FrontendUserAuthentication::getCookieName();
		$this->sessionId = $_COOKIE[$this->cookieName];

		// This is done to fixate the session id, even if the user is not logged in
		// TODO: this needs a better solution. At least this should be moved somewhere else...
//		$typoScriptFrontendController->fe_user->setKey('ses', 'naw_securedl', 'foo');
//		$typoScriptFrontendController->fe_user->storeSessionData();

		$this->ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : NULL;
	}

	/**
	 * Initializes the request context, when called from a backend request
	 */
	protected function initializeBackendContext() {
// Disabled for now, because we do only have php delivery script which is called in a frontend context (eID)
// TODO: decouple and refactor PHP delivery
//		$this->currentUser = $GLOBALS['BE_USER'];
//		if (!empty($this->currentUser->user['uid'])) {
//			$this->userId = (int)$this->currentUser->user['uid'];
//		}
//		if (!empty($this->currentUser->user['usergroup'])) {
//			$this->userGroupIds = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $this->currentUser->user['usergroup'], TRUE);
//		}
		$this->cookieName = BackendUserAuthentication::getCookieName();
		$this->sessionId = $_COOKIE[$this->cookieName];
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