<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\Cache;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

class EncodeCache extends AbstractCache
{
    public static function getCache(string $key): string
    {
        return self::$_cache[$key] ?? '';
    }
}
