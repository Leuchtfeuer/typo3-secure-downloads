<?php
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

/**
 * Class HtmlParser
 * @package Bitmotion\SecureDownloads\Parser
 */
class HtmlParser
{
    /**
     * @var int
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
     * Use raw regex of folderPattern (do not escape)
     *
     * @var bool
     */
    protected $folderPatternRaw;

    /**
     * @var string File extension pattern
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

    /**
     * @param HtmlParserDelegateInterface $delegate
     * @param array $settings
     */
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
            $this->fileExtensionPattern = '\\.(' . $this->fileExtensionPattern . ')';
        }

        $this->tagPattern = '/["\'](?:' . $this->domainPattern . ')?(\/?(?:' . $this->folderPattern . ')+?.*?(?:(?i)' . $this->fileExtensionPattern . '))["\']/i';
    }

    /**
     * @param string $accessProtectedDomain
     */
    public function setDomainPattern($accessProtectedDomain)
    {
        $this->domainPattern = $this->softQuoteExpression($accessProtectedDomain);
    }

    /**
     * Quotes special some characters for the regular expression.
     * Leave braces and brackets as is to have more flexibility in configuration.
     *
     * @param string $string
     *
     * @return string
     */
    static public function softQuoteExpression($string)
    {
        $string = str_replace('\\', '\\\\', $string);
        $string = str_replace(' ', '\ ', $string);
        $string = str_replace('/', '\/', $string);
        $string = str_replace('.', '\.', $string);
        $string = str_replace(':', '\:', $string);

        return $string;
    }

    /**
     * @param string $accessProtectedFileExtensions
     */
    public function setFileExtensionPattern($accessProtectedFileExtensions)
    {
        $this->fileExtensionPattern = $accessProtectedFileExtensions;
    }

    /**
     * @param string $accessProtectedFolders
     */
    public function setFolderPattern($accessProtectedFolders)
    {
        if ($this->folderPatternRaw) {
            $this->folderPattern = $accessProtectedFolders;
        } else {
            $this->folderPattern = $this->softQuoteExpression($accessProtectedFolders);
        }
    }

    /**
     * @param bool $folderPatternRaw
     */
    public function setFolderPatternRaw($folderPatternRaw)
    {
        $this->folderPatternRaw = $folderPatternRaw;
    }

    /**
     * @param integer $logLevel
     */
    public function setLogLevel($logLevel)
    {
        $this->logLevel = (int)$logLevel;
    }

    /**
     * Parses the HTML output and replaces the links to configured files with secured ones
     *
     * @param string $html
     *
     * @return string
     */
    public function parse($html)
    {
        if ($this->logLevel >= 1) {
            $time_start = $this->microtime_float();
        }

        $rest = $html;
        $result = '';
        $pattern = '/((data-[a-z0-9]*)|(href|src|poster))=["\']{1}(.*)["\']{1}/siU';


        while (preg_match($pattern, $html, $match)) {  // suchendes secured Verzeichnis
            $cont = explode($match[0], $html, 2);
            $vor = $cont[0];
            $tag = $match[0];

            if ($this->logLevel === 3) {
                DebuggerUtility::var_dump($tag, 'Tag:');
            }

            $rest = $cont[1];

            $tag = $this->parseTag($tag);

            $result .= $vor . $tag;
            $html = $rest;
        }

        if ($this->logLevel >= 1) {
            $time_end = $this->microtime_float();
            $time = $time_end - $time_start;
            DebuggerUtility::var_dump($time, 'Scriptlaufzeit');
        }

        return $result . $rest;
    }

    protected function microtime_float()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ($usec + $sec);
    }

    /**
     * Investigate the HTML-Tag...
     *
     * @param string $tag
     *
     * @return string
     */
    protected function parseTag($tag)
    {

        if (preg_match($this->tagPattern, $tag, $matchedUrls)) {
            $replace = $this->delegate->publishResourceUri($matchedUrls[1]);
            $tagexp = explode($matchedUrls[1], $tag, 2);
            $tag = $this->recursion($tagexp[0] . $replace, $tagexp[1]);

            // Some output for debugging
            if ($this->logLevel === 1) {
                DebuggerUtility::var_dump($tag, 'New output:');
            } elseif ($this->logLevel >= 2) {
                DebuggerUtility::var_dump($this->tagPattern, 'Regular Expression:');
                DebuggerUtility::var_dump($matchedUrls, 'Match:');
                DebuggerUtility::var_dump([$tagexp[0], $replace, $tagexp[1]], 'Build Tag:');
                DebuggerUtility::var_dump($tag, 'New output:');
            }
        }

        return $tag;
    }

    /**
     * Search recursive in the rest of the tag (e.g. for vHWin=window.open...).
     *
     * @param string $tag
     * @param string $tmp
     *
     * @return string
     */
    private function recursion($tag, $tmp)
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
