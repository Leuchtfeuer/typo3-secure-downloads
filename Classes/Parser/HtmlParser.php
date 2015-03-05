<?php
namespace Bitmotion\NawSecuredl\Parser;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Helmut Hummel (helmut.hummel@typo3.org)
 *  (c) 2013 Dietrich Heise ( typo3-ext(at)bitmotion.de )
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
 * Class HtmlParser
 * @package Bitmotion\NawSecuredl\Parser
 */
class HtmlParser {
	/**
	 * @var integer
	 */
	protected $logLevel = 0;

	/**
	 * Domain Pattern
	 * 
	 * @var string
	 */
	protected $domainPattern;

	/**
	 * Folder pattern
	 * 
	 * @var string
	 */
	protected $folderPattern;

	/**
	 * @var string File extension pattern
	 */
	protected $fileExtensionPattern;

	/**
	 * @var HtmlParserDelegateInterface
	 */
	protected $delegate;

	/**
	 * @param string $accessProtectedDomain
	 */
	public function setDomainPattern($accessProtectedDomain) {
		$this->domainPattern = $accessProtectedDomain;
	}

	/**
	 * @param string $accessProtectedFileExtensions
	 */
	public function setFileExtensionPattern($accessProtectedFileExtensions) {
		$this->fileExtensionPattern = $accessProtectedFileExtensions;
	}

	/**
	 * @param string $accessProtectedFolders
	 */
	public function setFolderPattern($accessProtectedFolders) {
		$this->folderPattern = $accessProtectedFolders;
	}

	/**
	 * @param integer $logLevel
	 */
	public function setLogLevel($logLevel) {
		$this->logLevel = (int)$logLevel;
	}

	/**
	 * @param HtmlParserDelegateInterface $delegate
	 * @param array $settings
	 */
	public function __construct(HtmlParserDelegateInterface $delegate, array $settings) {
		$this->delegate = $delegate;
		foreach ($settings as $settingKey => $setting) {
			$setterMethodName = 'set' . ucfirst($settingKey);
			if (method_exists($this, $setterMethodName)) {
				$this->$setterMethodName($setting);
			}
		}
		if (substr($this->fileExtensionPattern,0,1) !== '\\') {
			$this->fileExtensionPattern = '\\.(' . $this->fileExtensionPattern . ')';
		}
	}

	/**
	 * Parses the HTML output and replaces the links to configured files with secured ones
	 *
	 * @param string $html
	 * @return string
	 */
	public function parse($html) {
		$rest = $html;
		$result = '';
		while (preg_match('/(?i)(<link|<source|<a|<img|<video)+?.[^>]*(href|src|poster)=(\"??)([^\" >]*?)\\3[^>]*>/siU', $html, $match)) {  // suchendes secured Verzeichnis
			$cont = explode($match[0], $html, 2);
			$vor = $cont[0];
			$tag = $match[0];
			if ($this->logLevel === 2 || $this->logLevel === 3) {
				debug('tag:' . $tag);
			}

			$rest = $cont[1];

			if ($this->logLevel === 1 || $this->logLevel === 3) {
				debug(array('html-tag:'=>$tag));
			}

			$tag = $this->parseTag($tag, $this->folderPattern);

			$result .= $vor . $tag;
			$html = $rest;
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
	protected function parseTag($tag, $toSecureDirectoryExpression) {
		if (preg_match('/"(?:' . $this->softQuoteExpression($this->domainPattern) . ')?(\/?(?:' . $this->softQuoteExpression($toSecureDirectoryExpression) . ')+?.*?(?:(?i)' . $this->fileExtensionPattern . '))"/i', $tag, $matchedUrls)) {

			if ($this->logLevel === 2 || $this->logLevel === 3) {
				debug('/"(?:' . $this->softQuoteExpression($this->domainPattern) . ')?(\/?(?:' . $this->softQuoteExpression($toSecureDirectoryExpression) . ')+?.*?(?:(?i)' . $this->fileExtensionPattern . '))"/i');
			}
			if ($this->logLevel === 2 || $this->logLevel === 3) {
				debug($matchedUrls);
			}

			$replace = htmlspecialchars($this->delegate->publishResourceUri($matchedUrls[1]));
			$tagexp = explode($matchedUrls[1], $tag, 2);

			if ($this->logLevel === 2 || $this->logLevel === 3) {
				debug($tagexp[0]);
			}
			if ($this->logLevel === 2 || $this->logLevel === 3) {
				debug($replace);
			}
			if ($this->logLevel === 2 || $this->logLevel === 3) {
				debug($tagexp[1]);
			}

			$tag = $tagexp[0] . $replace;
			$tmp = $tagexp[1];

			// search in the rest on the tag (e.g. for vHWin=window.open...)
			if (preg_match('/\'(?:' . $this->softQuoteExpression($this->domainPattern) . ')?.*?(\/?(?:' . $this->softQuoteExpression($toSecureDirectoryExpression) . ')+?.*?(?:(?i)' . $this->fileExtensionPattern . '))\'/i', $tmp, $matchedUrls)) {
				$replace = htmlspecialchars($this->delegate->publishResourceUri($matchedUrls[1]));
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
	 * Quotes special some characters for the regular expression.
	 * Leave braces and brackets as is to have more flexibility in configuration.
	 *
	 * @param string $string
	 * @return string
	 */
	static public function softQuoteExpression($string) {
		$string = str_replace('\\', '\\\\', $string);
		$string = str_replace(' ', '\ ', $string);
		$string = str_replace('/', '\/', $string);
		$string = str_replace('.', '\.', $string);
		$string = str_replace(':', '\:', $string);
		return $string;
	}
}
