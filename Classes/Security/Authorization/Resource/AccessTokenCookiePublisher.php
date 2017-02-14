<?php
namespace Bitmotion\SecureDownloads\Security\Authorization\Resource;

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

use Bitmotion\SecureDownloads\Request\RequestContext;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 */

/**
 * Class AccessTokenCookiePublisher
 * @package Bitmotion\SecureDownloads\Security\Authorization\Resource
 */
class AccessTokenCookiePublisher implements SingletonInterface
{

    /**
     * @param RequestContext $requestContext
     */
    public function __construct(RequestContext $requestContext = null)
    {
        $this->requestContext = $requestContext ?: GeneralUtility::makeInstance('Bitmotion\\SecureDownloads\\Request\\RequestContext');
    }

    /**
     * This is the method called by TYPO3 when TSFE bootstrapping is ready but rendernig not done yet
     */
    public function checkDataSubmission()
    {
        $this->publishAccessTokenToCookie();
    }

    /**
     * Publishes the cookie if a user is logged in
     */
    protected function publishAccessTokenToCookie()
    {
        if ($this->requestContext->isUserLoggedIn()) {
            $token = $this->requestContext->getAccessToken();
            $contextPath = implode(
                '/',
                array_merge([$this->requestContext->getLocationId()], [sha1($token)])
            );
            setcookie(
                $this->requestContext->getCookieName(),
                $token,
                null,
                '/typo3temp/secure_downloads/' . $contextPath . '/',
                null,
                null,
                true
            );
        }
    }
}

