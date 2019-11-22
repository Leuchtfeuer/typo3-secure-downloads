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

use TYPO3\CMS\Core\SingletonInterface;

abstract class AbstractCache implements SingletonInterface
{
    protected static $_cache = [];

    public static function getCache(string $key)
    {
        return self::$_cache[$key] ?? null;
    }

    public static function hasCache(string $key): bool
    {
        return isset(self::$_cache[$key]);
    }

    public static function addCache(string $key, $value)
    {
        self::$_cache[$key] = $value;
    }
}
