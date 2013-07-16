<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2011 Dietrich Heise (typo3-ext(at)bitmotion.de)
 *  (c) 2009-2011 Helmut Hummel (typo3-ext(at)bitmotion.de)
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

/**
 * @author	Dietrich Heise <typo3-ext(at)bitmotion.de>
 * @author	Helmut Hummel <typo3-ext(at)bitmotion.de>
 */
class tx_nawsecuredl {

	/**
	 * Extension Configuration
	 *
	 * @var array
	 */
	protected $extensionConfiguration;

	/**
	 * The TYPO3 frontend object
	 *
	 * @var tslib_fe
	 */
	protected $objFrontend;


	/**
	 * Constructor
	 */
	public function __construct() {
		$this->objFrontend = $GLOBALS['TSFE'];
		$this->extensionConfiguration = $this->getExtensionConfiguration();
	}

	/**
	 * Get extension configuration
	 *
	 * @return array
	 */
	protected function getExtensionConfiguration() {
		return unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['naw_securedl']);
	}

	/**
	 * This method is called by the frontend rendering hook contentPostProc->output
	 *
	 * @param array $parameters
	 * @param tslib_fe $objFrontend
	 */
	public function parseFE(&$parameters, $objFrontend) {
		$this->objFrontend = $objFrontend;

		// Parsing the content if not explicitly disabled
		if (!isset($this->objFrontend->config['config']['tx_nawsecuredl_enable'])
			|| $this->objFrontend->config['config']['tx_nawsecuredl_enable'] !== '0') {
			$this->objFrontend->content = $this->parseContent($this->objFrontend->content);
		}
	}

	/**
	 * Parses the HTML output and replaces the links to configured files with secured ones
	 *
	 * @param string $strContent
	 * @return string
	 */
	public function parseContent($strContent) {
		$this->extensionConfiguration = $this->getExtensionConfiguration();
		$rest = $strContent;
		$result = '';
		while (preg_match('/(?i)(<source|<a|<img)+?.[^>]*(href|src)=(\"??)([^\" >]*?)\\3[^>]*>/siU', $strContent, $match)) {  // suchendes secured Verzeichnis
			$cont = explode($match[0], $strContent, 2);
			$vor = $cont[0];
			$tag = $match[0];
			if ($this->extensionConfiguration['debug'] == '2' || $this->extensionConfiguration['debug'] == '3') {
				debug('tag:' . $tag);
			}

			$rest = $cont[1];

			if ($this->extensionConfiguration['debug'] == '1' || $this->extensionConfiguration['debug'] == '3') {
				debug(array('html-tag:'=>$tag));
			}

			$tag = $this->parseHtmlTag($tag, $this->extensionConfiguration['securedDirs']);

			$result .= $vor . $tag;
			$strContent = $rest;
		}
		return $result . $rest;
	}

	/**
	 * Investigate the HTML-Tag...
	 *
	 * @param $tag
	 * @param $toSecureDirectoryExpression
	 * @return string
	 */
	protected function parseHtmlTag($tag, $toSecureDirectoryExpression) {
		if (preg_match('/"(?:' . $this->softQuoteExpression($this->extensionConfiguration['domain']) . ')?(\/?(?:' . $this->softQuoteExpression($toSecureDirectoryExpression) . ')+?.*?(?:' . $this->getFileTypeExpression($this->extensionConfiguration['filetype']) . '))"/i', $tag, $matchedUrls)) {

			if ($this->extensionConfiguration['debug'] == '2' || $this->extensionConfiguration['debug'] == '3') {
				debug('/"(?:' . $this->softQuoteExpression($this->extensionConfiguration['domain']) . ')?(\/?(?:' . $this->softQuoteExpression($toSecureDirectoryExpression) . ')+?.*?(?:' . $this->getFileTypeExpression($this->extensionConfiguration['filetype']) . '))"/i');
			}
			if ($this->extensionConfiguration['debug'] == '2' || $this->extensionConfiguration['debug'] == '3') {
				debug($matchedUrls);
			}

			$replace = htmlspecialchars($this->makeSecure($matchedUrls[1]));
			$tagexp = explode($matchedUrls[1], $tag, 2);

			if ($this->extensionConfiguration['debug'] == '2' || $this->extensionConfiguration['debug'] == '3') {
				debug($tagexp[0]);
			}
			if ($this->extensionConfiguration['debug'] == '2' || $this->extensionConfiguration['debug'] == '3') {
				debug($replace);
			}
			if ($this->extensionConfiguration['debug'] == '2' || $this->extensionConfiguration['debug'] == '3') {
				debug($tagexp[1]);
			}

			$tag = $tagexp[0] . $replace;
			$tmp = $tagexp[1];

			// search in the rest on the tag (e.g. for vHWin=window.open...)
			if (preg_match('/\'(?:' . $this->softQuoteExpression($this->extensionConfiguration['domain']) . ')?.*?(\/?(?:' . $this->softQuoteExpression($toSecureDirectoryExpression) . ')+?.*?(?:' . $this->getFileTypeExpression($this->extensionConfiguration['filetype']) . '))\'/i', $tmp, $matchedUrls)) {
				$replace = htmlspecialchars($this->makeSecure($matchedUrls[1]));
				$tagexp = explode($matchedUrls[1], $tmp, 2);
				$add = $tagexp[0] . '/' . $replace . $tagexp[1];
			} else {
				$add = $tagexp[1];
			}

			$tag .= $add;
		}
		return $tag;
	}

	/**
	 * Transforms a relative file URL to a secure download protected URL
	 *
	 * @param string $originalUrl
	 * @return string
	 */
	public function makeSecure($originalUrl) {
		if ($this->objFrontend->fe_user->user['uid']){
			$frontendUserId = $this->objFrontend->fe_user->user['uid'];
			$frontendUserGroupIds = t3lib_div::trimExplode(',', $this->objFrontend->fe_user->user['usergroup'], TRUE);
		} else {
			$frontendUserId = 0;
			$frontendUserGroupIds = array( 0 );
		}

		$cacheTimeToAdd = $this->extensionConfiguration['cachetimeadd'];

		if ($this->objFrontend->page['cache_timeout'] == 0){
			$timeout = 86400 + $GLOBALS['EXEC_TIME'] + $cacheTimeToAdd;
		} else {
			$timeout =  $this->objFrontend->page['cache_timeout'] + $GLOBALS['EXEC_TIME'] + $cacheTimeToAdd;
		}

		// $originalUrl contains the URL which is already url encoded by TYPO3.
		// Since we check the hash in the output script using the decoded filename we must decode it here also!
		$hash = $this->getHash($frontendUserId . implode(',', $frontendUserGroupIds) . rawurldecode($originalUrl) . $timeout);

		// Parsing the link format, and return this instead (an flexible link format is useful for mod_rewrite tricks ;)
		if (!isset($this->extensionConfiguration['linkFormat']) || strpos($this->extensionConfiguration['linkFormat'], '###FEGROUPS###') === FALSE) {
			$this->extensionConfiguration['linkFormat'] = 'index.php?eID=tx_nawsecuredl&u=###FEUSER###&g=###FEGROUPS###&file=###FILE###&t=###TIMEOUT###&hash=###HASH###';
		}

		$tokens = array('###FEUSER###', '###FEGROUPS###', '###FILE###', '###TIMEOUT###', '###HASH###');
		$replacements = array($frontendUserId, rawurlencode(implode(',', $frontendUserGroupIds)), $originalUrl, $timeout, $hash);
		$transformedUrl = str_replace($tokens, $replacements, $this->extensionConfiguration['linkFormat']);

		// Hook for makeSecure:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl.php']['makeSecure'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl.php']['makeSecure'] as $_funcRef)   {
				$transformedUrl = t3lib_div::callUserFunction($_funcRef, $transformedUrl, $this);
			}
		}

		return $transformedUrl;
	}

	/**
	 * @param $string
	 * @return string
	 */
	protected function getHash($string) {
		return t3lib_div::hmac($string);
	}

	/**
	 * Returns a case insensitive regular expression based on
	 * lowercase input
	 *
	 * @param string $string
	 * @return string
	 */
	protected function getFileTypeExpression($string) {
		return '(?i)' . $string;
	}

	/**
	 * Quotes special some characters for the regular expression.
	 * Leave braces and brackets as is to have more flexibility in configuration.
	 *
	 * @param string $string
	 * @return string
	 */
	protected function softQuoteExpression($string) {
		$string = str_replace('\\', '\\\\', $string);
		$string = str_replace(' ', '\ ', $string);
		$string = str_replace('/', '\/', $string);
		$string = str_replace('.', '\.', $string);
		$string = str_replace(':', '\:', $string);
		return $string;
	}

}

// Include extension?
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/naw_securedl/class.tx_nawsecuredl.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/naw_securedl/class.tx_nawsecuredl.php']);
}
?>