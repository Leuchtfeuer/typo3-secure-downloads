<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Cache;

use Bitmotion\SecureDownloads\Domain\Transfer\Download;

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
 * Stores decoded JSON web token data.
 */
class DecodeCache extends AbstractCache
{
    public static function getCache(string $key): Download
    {
        return parent::getCache($key);
    }
}
