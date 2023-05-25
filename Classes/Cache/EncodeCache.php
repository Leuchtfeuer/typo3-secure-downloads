<?php

declare(strict_types=1);
namespace Leuchtfeuer\SecureDownloads\Cache;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

/**
 * Stores encoded JSON web token data.
 */
class EncodeCache extends AbstractCache
{
    /**
     * Retrieves data from the cache.
     *
     * @param string $key The cache key.
     *
     * @return string The cache data or an empty string if cache entry does not exist.
     */
    public static function getCache(string $key): string
    {
        return self::$_cache[$key] ?? '';
    }
}
