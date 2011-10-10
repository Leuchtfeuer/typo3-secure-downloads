<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2011 Dietrich Heise (typo3-ext(at)naw.info)
*  (c) 2009-2011 Helmut Hummel (typo3-ext(at)naw.info)
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
 * @author	Dietrich Heise <typo3-ext(at)naw.info>
 * @author	Helmut Hummel <typo3-ext(at)naw.info>
 */
class tx_nawsecuredl {

	/**
	 * This method is called by the frontend rendering hook contentPostProc-output
	 *
	 * @param array $parameters
	 * @param tslib_fe $objFrontend
	 */
	public function parseFE(&$parameters, $objFrontend) {
		if ($objFrontend->config['config']['tx_nawsecuredl_enable'] !== '0') {
			$objFrontend->content = $this->parseContent($objFrontend->content);
		}
	}

	/**
	 * Parses the HTML output and replaces the links to configured files with secured ones
	 *
	 * @param	string $strContent
	 * @return	string
	 */
	public function parseContent($strContent) {
		$sitepath = t3lib_div::getIndpEnv('REQUEST_URI');
		$this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['naw_securedl']);
		$rest = $strContent;

		$result = '';
		while (preg_match('/(?i)(<source|<a|<img)+?.[^>]*(href|src)=(\"??)([^\" >]*?)\\3[^>]*>/siU', $strContent, $match)) {  // suchendes secured Verzeichnis

				$cont = explode($match[0], $strContent, 2);
				$vor = $cont[0];
					$tag = $match[0];
					if ($this->extConf['debug'] == '2' || $this->extConf['debug'] == '3') {
						debug('tag:' . $tag);
					}

					$rest = $cont[1];

				if ($this->extConf['debug'] == '1' || $this->extConf['debug'] == '3') {
					debug(array('html-tag:'=>$tag));
				}

				// investigate the HTML-Tag...
				if (preg_match('/"(?:'.$this->modifiyregex($this->extConf['domain']).')?(\/?(?:'.$this->modifiyregex($this->extConf['securedDirs']).')+?.*?(?:'.$this->modifyfiletypes($this->extConf['filetype']).'))"/i', $tag,$match1)) {

					if ($this->extConf['debug'] == '2' || $this->extConf['debug'] == '3') {
						debug('/"(?:'.$this->modifiyregex($this->extConf['domain']).')?(\/?(?:'.$this->modifiyregex($this->extConf['securedDirs']).')+?.*?(?:'.$this->modifyfiletypes($this->extConf['filetype']).'))"/i');
					}
					if ($this->extConf['debug'] == '2' || $this->extConf['debug'] == '3') {
						debug($match1);
					}

					$replace = $this->makeSecure($match1[1]);
					$tagexp = explode ($match1[1], $tag , 2 );

					if ($this->extConf['debug'] == '2' || $this->extConf['debug'] == '3') {
						debug($tagexp[0]);
					}
					if ($this->extConf['debug'] == '2' || $this->extConf['debug'] == '3') {
						debug($replace);
					}
					if ($this->extConf['debug'] == '2' || $this->extConf['debug'] == '3') {
						debug($tagexp[1]);
					}

					$tag = $tagexp[0] . $replace;
					$tmp = $tagexp[1];

					// search in the rest on the tag (e.g. for vHWin=window.open...)
					if (preg_match('/\'(?:'.$this->modifiyregex($this->extConf['domain']).')?.*?(\/?(?:'.$this->modifiyregex($this->extConf['securedDirs']).')+?.*?(?:'.$this->modifyfiletypes($this->extConf['filetype']).'))\'/i', $tmp,$match1)){
						$replace = $this->makeSecure($match1[1]);
						$tagexp = explode ($match1[1], $tmp , 2 );
						$add = $tagexp[0].'/'.$replace.$tagexp[1];
					}else{
						$add = $tagexp[1];
					}

					$tag .= $add;
				}
				$result .= $vor . $tag;
				$strContent = $rest;
		}
		return $result . $rest;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$element: ...
	 * @return	[type]		...
	 */
	function makeSecure($element) {
		if ($GLOBALS['TSFE']->fe_user->user['uid']){
			$this->feuser = $GLOBALS['TSFE']->fe_user->user['uid'];
		} else {
			$this->feuser = 0;
		}

		$cachetimeadd = $this->extConf['cachetimeadd'];

		if ($GLOBALS['TSFE']->page['cache_timeout'] == 0){
			$timeout = 86400 + time() + $cachetimeadd;
		}else{
			$timeout =  $GLOBALS['TSFE']->page['cache_timeout'] + time() + $cachetimeadd;
		}

			// $element contains the URL which is already urlencoded by TYPO3.
			// Since we check the hash in the output script using the decoded filename we must decode it here also!
		$data = $this->feuser . rawurldecode($element) . $timeout;
		$hash = t3lib_div::hmac($data);

			// Parsing the linkformat, and return this instead (an flexible linkformat is usefull for mod_rewrite tricks ;)
		if (!isset($this->extConf['linkFormat'])) {
			$this->extConf['linkFormat'] = 'index.php?eID=tx_nawsecuredl&u=###FEUSER###&file=###FILE###&t=###TIMEOUT###&hash=###HASH###';
		}

		$tokens = array('###FEUSER###', '###FILE###', '###TIMEOUT###', '###HASH###');
		$replacements = array($this->feuser, $element, $timeout, $hash);
		$returnPath = htmlspecialchars(str_replace($tokens, $replacements, $this->extConf['linkFormat']));

			// Hook for makeSecure:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl.php']['makeSecure'])) {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl.php']['makeSecure'] as $_funcRef)   {
				$returnPath = t3lib_div::callUserFunction($_funcRef, $returnPath, $this);
			}
		}

		return $returnPath;
	}

	/**
	 * What the heck is this for??
	 * 
	 * For Example:
	 * IN     : pdf|jpe?g
	 * Returns: [pP][dD][fF]|[jJ][pP][eE]?[gG]
	 *
	 * @param string $string
	 */
	protected function modifyfiletypes($string) {
		$chars = preg_split('//', $string);
		$out = '';
		foreach ($chars as $i) {
			if (preg_match('/\w/', $i)) {
				$out .= '[' . strtoupper($i) . strtolower($i) . ']';
			} else {
				$out .= $i;
			}
		}
		return $out;
	}

	/**
	 * Quotes special characters for the regular expression
	 *
	 * @todo: Check if this can be replaced by preg_quote()
	 * @param string $string
	 */
	protected function modifiyregex($string) {
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