<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Cache;

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

use TYPO3\CMS\Core\SingletonInterface;

/**
 * Abstract class for caching data.
 * Mainly used for storing token data.
 */
abstract class AbstractCache implements SingletonInterface
{
    /**
     * @var array Contains the cache entries for given cache.
     */
    protected static $_cache = [];

    /**
     * Retrieves data from the cache.
     *
     * @param string $key The cache key.
     *
     * @return mixed The cache data or null if cache entry does not exist.
     */
    public static function getCache(string $key)
    {
        return self::$_cache[$key] ?? null;
    }

    /**
     * Checks whether a cache entry for given key exists.
     *
     * @param string $key The cache key
     *
     * @return bool Returns true, if cache with given key exists; false otherwise.
     */
    public static function hasCache(string $key): bool
    {
        return isset(self::$_cache[$key]);
    }

    /**
     * Adds data to the cache. Existing entries will be overwritten.
     *
     * @param string $key The cache key.
     * @param mixed  $value The cache data.
     */
    public static function addCache(string $key, $value): void
    {
        self::$_cache[$key] = $value;
    }
}
