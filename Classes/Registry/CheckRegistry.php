<?php

declare(strict_types=1);

/*
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\SecureDownloads\Registry;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Leuchtfeuer\SecureDownloads\Exception\ClassNotFoundException;
use Leuchtfeuer\SecureDownloads\Exception\InvalidClassException;
use Leuchtfeuer\SecureDownloads\Security\AbstractCheck;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CheckRegistry extends AbstractRegistry
{
    protected static $checks = [];

    /**
     * @param string $identifier        An unique identifier for the object
     * @param string $className         The class name of the object
     * @param int    $priority          The priority of the registered object
     * @param bool   $overwriteExisting Whether an existing entry should be overwritten or not
     *
     * @throws ClassNotFoundException
     * @throws InvalidClassException
     */
    public static function register(string $identifier, string $className, int $priority = 0, bool $overwriteExisting = false): void
    {
        if (isset(self::$checks[$identifier]) && $overwriteExisting === false) {
            // Do nothing. Maybe log this in future.
            return;
        }

        self::$checks[$identifier] = [
            'class' => self::getCheckFromClassName($className),
            'priority' => $priority,
        ];

        self::sortByPriority(self::$checks);
    }

    /**
     * @return array The registered security checks
     */
    public static function getChecks(): array
    {
        return self::$checks;
    }

    /**
     * Instantiates the security check object from its name.
     *
     * @param string $className The class name of the check
     *
     * @return AbstractCheck The actual security check object
     *
     * @throws ClassNotFoundException
     * @throws InvalidClassException
     */
    private static function getCheckFromClassName(string $className): AbstractCheck
    {
        if (!class_exists($className)) {
            throw new ClassNotFoundException(
                sprintf('Class "%s" not found.', $className),
                1588837466
            );
        }

        $check = GeneralUtility::makeInstance($className);

        if (!$check instanceof AbstractCheck) {
            throw new InvalidClassException(
                sprintf('Class "%s" must extend "%s"', $className, AbstractCheck::class),
                1588837696
            );
        }

        return $check;
    }
}
