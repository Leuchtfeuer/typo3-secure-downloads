<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\Parser;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Bitmotion GmbH (typo3-ext@bitmotion.de)
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

use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class HtmlParser
{
    /**
     * @var int
     */
    protected $logLevel = 0;

    /**
     * @var string
     */
    protected $domainPattern;

    /**
     * @var string
     */
    protected $folderPattern;

    /**
     * @var string
     */
    protected $fileExtensionPattern;

    /**
     * @var HtmlParserDelegateInterface
     */
    protected $delegate;

    /**
     * @var string
     */
    protected $tagPattern;

    public function __construct(HtmlParserDelegateInterface $delegate, array $settings)
    {
        $this->delegate = $delegate;
        foreach ($settings as $settingKey => $setting) {
            $setterMethodName = 'set' . ucfirst($settingKey);
            if (method_exists($this, $setterMethodName)) {
                $this->$setterMethodName($setting);
            }
        }
        if (substr($this->fileExtensionPattern, 0, 1) !== '\\') {
            $fileExtensionPattern = $this->fileExtensionPattern;
            if (trim($fileExtensionPattern) === '*') {
                $fileExtensionPattern = '\\w+';
            }

            $this->fileExtensionPattern = '\\.(' . $fileExtensionPattern . ')';
        }

        $this->tagPattern = '/["\'](?:' . $this->domainPattern . ')?(\/?(?:' . $this->folderPattern . ')+?.*?(?:(?i)' . $this->fileExtensionPattern . '))["\']?/i';
    }

    public function setDomainPattern(string $accessProtectedDomain)
    {
        if (!empty($GLOBALS['TSFE']->absRefPrefix) && $GLOBALS['TSFE']->absRefPrefix !== '/') {
            $accessProtectedDomain .= '|' . $GLOBALS['TSFE']->absRefPrefix;
        }

        $this->domainPattern = $this->softQuoteExpression($accessProtectedDomain);
    }

    /**
     * Quotes special some characters for the regular expression.
     * Leave braces and brackets as is to have more flexibility in configuration.
     */
    public static function softQuoteExpression(string $string): string
    {
        $string = str_replace('\\', '\\\\', $string);
        $string = str_replace(' ', '\ ', $string);
        $string = str_replace('/', '\/', $string);
        $string = str_replace('.', '\.', $string);
        $string = str_replace(':', '\:', $string);

        return $string;
    }

    public function setFileExtensionPattern(string $accessProtectedFileExtensions)
    {
        $this->fileExtensionPattern = $accessProtectedFileExtensions;
    }

    public function setFolderPattern(string $accessProtectedFolders)
    {
        $this->folderPattern = $this->softQuoteExpression($accessProtectedFolders);
    }

    public function setLogLevel(int $logLevel)
    {
        $this->logLevel = $logLevel;
    }

    /**
     * Parses the HTML output and replaces the links to configured files with secured ones
     */
    public function parse(string $html): string
    {
        if ($this->logLevel >= 1) {
            $time_start = $this->microtime_float();
        }

        $result = '';
        $pattern = '/((((data|ng|v)-[a-z0-9]*)|(href|src|poster))=["\']{1}(.*)["\']{1})|url\((.*)\)/siU';

        while (preg_match($pattern, $html, $match)) {
            $htmlContent = explode($match[0], $html, 2);

            if ($this->logLevel === 3) {
                DebuggerUtility::var_dump($match[0], 'Tag:');
            }

            // Parse tag
            $tag = $this->parseTag($match[0]);

            // Add tag to HTML before matching tag
            $result .= $htmlContent[0] . $tag;

            // all HTML after matching tag
            $html = $htmlContent[1];
        }

        if ($this->logLevel >= 1) {
            $time_end = $this->microtime_float();
            $time = $time_end - $time_start;
            DebuggerUtility::var_dump($time, 'Scriptlaufzeit');
        }

        return $result . $html;
    }

    protected function microtime_float(): float
    {
        list($usec, $sec) = explode(' ', microtime());

        return $usec + $sec;
    }

    /**
     * Investigate the HTML-Tag...
     */
    protected function parseTag(string $tag): string
    {
        if (preg_match($this->tagPattern, $tag, $matchedUrls)) {
            $resourceUri = $matchedUrls[1];
            $replace = $this->delegate->publishResourceUri($resourceUri);
            $tagParts = explode($matchedUrls[1], $tag, 2);
            $tag = $this->recursion(rtrim($tagParts[0], '/') . '/' . $replace, $tagParts[1]);

            // Some output for debugging
            if ($this->logLevel === 1) {
                DebuggerUtility::var_dump($tag, 'New output:');
            } elseif ($this->logLevel >= 2) {
                DebuggerUtility::var_dump($this->tagPattern, 'Regular Expression:');
                DebuggerUtility::var_dump($matchedUrls, 'Match:');
                DebuggerUtility::var_dump([$tagParts[0], $replace, $tagParts[1]], 'Build Tag:');
                DebuggerUtility::var_dump($tag, 'New output:');
            }
        }

        return $tag;
    }

    /**
     * Search recursive in the rest of the tag (e.g. for vHWin=window.open...).
     */
    private function recursion(string $tag, string $tmp): string
    {
        if (preg_match($this->tagPattern, $tmp, $matchedUrls)) {
            $replace = $this->delegate->publishResourceUri($matchedUrls[1]);
            $tagexp = explode($matchedUrls[1], $tmp, 2);

            if ($this->logLevel >= 2) {
                DebuggerUtility::var_dump([$tagexp[0], $replace, $tagexp[1]], 'Further Match:');
            }

            $tag .= $tagexp[0] . '/' . $replace;

            return $this->recursion($tag, $tagexp[1]);
        }

        return $tag . $tmp;
    }
}
