<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Parser;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @deprecated Parsing the generated HTML is deprecated. All public URLs to files should be retrieved by TYPO3 API.
 */
class HtmlParser implements LoggerAwareInterface
{
    use LoggerAwareTrait;

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

        if (!$this->logger instanceof LoggerInterface) {
            $this->setLogger(GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__));
        }

        foreach ($settings as $settingKey => $setting) {
            $setterMethodName = 'set' . ucfirst($settingKey);
            if (method_exists($this, $setterMethodName)) {
                $this->$setterMethodName($setting);
            }
        }
        if (substr($this->fileExtensionPattern, 0, 1) !== '\\') {
            $fileExtensionPattern = $this->fileExtensionPattern;
            if (trim($fileExtensionPattern) === ExtensionConfiguration::FILE_TYPES_WILDCARD) {
                $fileExtensionPattern = '\\w+';
            }
            $this->fileExtensionPattern = '\\.(' . $fileExtensionPattern . ')';
        }

        $this->tagPattern = '/["\'](?:' . $this->domainPattern . ')?(\/?(?:' . $this->folderPattern . ')+?.*?(?:(?i)' . $this->fileExtensionPattern . '))["\']?/i';
    }

    public function setDomainPattern(string $accessProtectedDomain): void
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

    public function setFileExtensionPattern(string $accessProtectedFileExtensions): void
    {
        $this->fileExtensionPattern = $accessProtectedFileExtensions;
    }

    public function setFolderPattern(string $accessProtectedFolders): void
    {
        $this->folderPattern = $this->softQuoteExpression($accessProtectedFolders);
    }

    public function setLogLevel(int $logLevel): void
    {
        $this->logLevel = $logLevel;
    }

    public function parse(string $html): string
    {
        if ($this->logLevel >= 1) {
            $parseStartTime = $this->microtime_float();
        }

        $result = '';
        $pattern = '/((((data|ng|v)-[a-z0-9]*)|(href|src|poster))=["\']{1}(.*)["\']{1})|url\((.*)\)/siU';

        while (preg_match($pattern, $html, $match)) {
            $htmlContent = explode($match[0], $html, 2);

            if ($this->logLevel === 3) {
                $this->logger->debug(sprintf('Found matching tag: %s', $match[0]));
            }

            // Parse tag
            $tag = $this->parseTag($match[0]);

            // Add tag to HTML before matching tag
            $result .= $htmlContent[0] . $tag;

            // all HTML after matching tag
            $html = $htmlContent[1];
        }

        if ($this->logLevel >= 1) {
            $parseFinishTime = $this->microtime_float();
            $executionTime = $parseFinishTime - $parseStartTime;
            $this->logger->notice(sprintf('Script runtime: %s', $executionTime));
        }

        return $result . $html;
    }

    protected function microtime_float(): float
    {
        [$usec, $sec] = explode(' ', microtime());

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
                $this->logger->notice(sprintf('New output: %s', $tag));
            } elseif ($this->logLevel >= 2) {
                $this->logger->info(sprintf('Regular expression: %s', $this->tagPattern));
                $this->logger->info(sprintf('Matching URLs: %s', implode(',', $matchedUrls)));
                $this->logger->notice(sprintf('Build tag (part 1): %s', $tagParts[0]));
                $this->logger->notice(sprintf('Build tag (replace): %s', $replace));
                $this->logger->notice(sprintf('Build tag (part 1): %s', $tagParts[0]));
                $this->logger->notice(sprintf('New output: %s', $tag));
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
                $this->logger->info(sprintf('Futher matches (part 1): %s', $tagexp[0]));
                $this->logger->info(sprintf('Futher matches (replace): %s', $replace));
                $this->logger->info(sprintf('Futher matches (part 2): %s', $tagexp[1]));
            }

            $tag .= $tagexp[0] . '/' . $replace;

            return $this->recursion($tag, $tagexp[1]);
        }

        return $tag . $tmp;
    }
}
