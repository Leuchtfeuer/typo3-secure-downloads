<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Core;

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
 * @deprecated Will be removed in version 5.
 */
class ObjectManager implements SingletonInterface
{
    /**
     * Registered alternative implementations of a class
     * e.g. used to know the class for a AbstractClass or a Dependency
     */
    private $alternativeImplementations = [];

    /**
     * register a classname that should be used if a dependency is required.
     * e.g. used to define default class for a interface
     *
     * @param string $className            The class name
     * @param string $alternativeClassName The alternative class name
     *
     * @throws \InvalidArgumentException
     */
    public function registerImplementation(string $className, string $alternativeClassName): void
    {
        if (!isset($this->alternativeImplementations[$className])) {
            if (interface_exists($className)) {
                throw new \InvalidArgumentException('Cannot register implementation for Interfaces', 1378921763);
            }

            $this->alternativeImplementations[$className] = $alternativeClassName;
            class_alias($alternativeClassName, $className);
        }
        // Ignore this error
    }
}
