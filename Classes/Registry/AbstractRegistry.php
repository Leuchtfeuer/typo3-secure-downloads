<?php

declare(strict_types=1);
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
    /**
     * @param string $identifier        An unique identifier for the object
     * @param string $className         The class name of the object
     * @param int    $priority          The priority of the registered object
     * @param bool   $overwriteExisting Whether an existing entry should be overwritten or not
     */
    abstract public static function register(string $identifier, string $className, int $priority = 0, bool $overwriteExisting = false): void;

    /**
     * Sorts given elements by its priority.
     *
     * @param array $elements The elements to be sorted
     */
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
