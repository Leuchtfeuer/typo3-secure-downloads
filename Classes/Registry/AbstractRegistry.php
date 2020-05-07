<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Registry;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use TYPO3\CMS\Core\SingletonInterface;

abstract class AbstractRegistry implements SingletonInterface
{
    abstract public static function register(string $identifier, string $className, int $priority = 0, bool $overwriteExisting = false): void;

    protected static function sortByPriority(array &$elements): void
    {
        usort($elements, function ($a, $b) {
            if ($a['priority'] === $b['priority']) {
                return 0;
            }

            return $a['priority'] > $b['priority'] ? -1 : 1;
        });
    }
}
