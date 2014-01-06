<?php
namespace Bitmotion\NawSecuredl\Security\Authorization\Resource;

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

use Bitmotion\NawSecuredl\Request\RequestContext;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class implements a hook and publishes a cookie with the access token suitable for the current request
 */
class AccessTokenCookiePublisher implements SingletonInterface {

	/**
	 * @param RequestContext $requestContext
	 */
	public function __construct(RequestContext $requestContext = NULL) {
		$this->requestContext = $requestContext ?: GeneralUtility::makeInstance('Bitmotion\\NawSecuredl\\Request\\RequestContext');
	}

	/**
	 * This is the method called by TYPO3 when TSFE bootstrapping is ready but rendernig not done yet
	 */
	public function checkDataSubmission() {
		$this->publishAccessTokenToCookie();
	}

	/**
	 * Publishes the cookie if a user is logged in
	 */
	protected function publishAccessTokenToCookie() {
		if ($this->requestContext->isUserLoggedIn()) {
			$token = $this->requestContext->getAccessToken();
			// Check does not work because the cookie path is set to be only valid in publishing directory
//			if ($_COOKIE[$this->requestContext->getCookieName()] !== $token) {
				$contextPath = implode('/', array_merge(
					array($this->requestContext->getLocationId()),
					array(sha1($token))
				));
				setcookie(
					$this->requestContext->getCookieName(),
					$token,
					NULL,
					'/typo3temp/secure_downloads/' . $contextPath . '/',
					NULL,
					NULL,
					TRUE
				);
//			}
		}
	}
}

