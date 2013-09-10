<?php
namespace Bitmotion\NawSecuredl\Security\Authorization\Resource;

/*                                                                        *
 * This script belongs to the TYPO3 Flow framework.                       *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 * of the License, or (at your option) any later version.                 *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
use Bitmotion\NawSecuredl\Request\RequestContext;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * An access restriction publisher that publishes .htaccess files to configure apache2 restrictions
 */
class Apache2AccessRestrictionPublisher implements AccessRestrictionPublisherInterface, SingletonInterface {

	/**
	 * @var RequestContext
	 */
	protected $requestContext;

	private $htaccessTemplate = '
Order deny,allow
Deny from all
SetEnvIfNoCase Cookie "^%s=%s$" session_match
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

	/**
	 * @param RequestContext $requestContext
	 */
	public function __construct(RequestContext $requestContext = NULL) {
		$this->requestContext = $requestContext ?: GeneralUtility::makeInstance('Bitmotion\\NawSecuredl\\Request\\RequestContext');
	}


	/**
	 * Publishes an Apache2 .htaccess file which allows access to the given directory only for the current session remote ip
	 *
	 * @param string $path The path to publish the restrictions for
	 * @return void
	 */
	public function publishAccessRestrictionsForPath($path) {
		$remoteAddress = $this->requestContext->getIpAddress();
		$cookieName = $this->requestContext->getCookieName();
		$cookieValue = $_COOKIE[$cookieName];

		if ($remoteAddress !== NULL) {
			$content = sprintf(
				$this->htaccessTemplateIpCheck,
				$remoteAddress,
				$cookieName,
				$cookieValue
			);
		} else {
			$content = sprintf(
				$this->htaccessTemplate,
				$cookieName,
				$cookieValue
			);
		}
		file_put_contents($path . '.htaccess', $content);
	}
}

?>