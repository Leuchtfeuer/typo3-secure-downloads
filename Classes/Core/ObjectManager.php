<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\Core;

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

class ObjectManager implements SingletonInterface
{
    /**
     * Registered alternative implementations of a class
     * e.g. used to know the class for a AbstractClass or a Dependency
     */
    private $alternativeImplementation = [];

    /**
     * register a classname that should be used if a dependency is required.
     * e.g. used to define default class for a interface
     *
     * @throws \InvalidArgumentException
     */
    public function registerImplementation(string $className, string $alternativeClassName)
    {
        if (!array_key_exists($className, $this->alternativeImplementation)) {
            if (interface_exists($className)) {
                throw new \InvalidArgumentException('Cannot register implementation for Interfaces', 1378921763);
            }
            $this->alternativeImplementation[$className] = $alternativeClassName;
            class_alias($alternativeClassName, $className);
        }
        // Ignore this error
    }
}
