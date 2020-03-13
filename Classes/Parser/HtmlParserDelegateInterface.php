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

/**
 * @deprecated Parsing the generated HTML is deprecated. All public URLs to files should be retrieved by TYPO3 API.
 */
interface HtmlParserDelegateInterface
{
    public function publishResourceUri(string $resourceUri): string;
}
