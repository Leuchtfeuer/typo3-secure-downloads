<?php
declare(strict_types=1);
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

/**
 * An access restriction publisher that publishes .htaccess files to configure apache2 restrictions
 */
class Apache2AccessRestrictionPublisher implements AccessRestrictionPublisherInterface, SingletonInterface
{
    protected $requestContext;

    private $htaccessTemplate = '
Order deny,allow
Deny from all
SetEnvIfNoCase Cookie "(^| )%s=%s($|;)" session_match
Allow from env=session_match
';

    private $htaccessTemplateIpCheck = '
Order deny,allow
Deny from all
SetEnvIf Remote_Addr "^" ip_ok=0
SetEnvIfNoCase Remote_Addr "^%s$" ip_ok=1
SetEnvIfNoCase Cookie "(^| )%s=%s($|;)" session_match
SetEnvIfNoCase ip_ok 0 !session_match
Allow from env=session_match
';

    public function __construct(RequestContext $requestContext)
    {
        $this->requestContext = $requestContext;
    }

    /**
     * Publishes an Apache2 .htaccess file which allows access to the given directory only for the current session
     * remote ip
     *
     * @param string $path The path to publish the restrictions for
     *
     * @return void
     */
    public function publishAccessRestrictionsForPath(string $path)
    {
        $cookieName = $this->requestContext->getCookieName();
        $cookieValue = $this->requestContext->getAccessToken();
        $content = sprintf($this->htaccessTemplate, $cookieName, $cookieValue);

        file_put_contents($path . '.htaccess', $content);
    }
}
