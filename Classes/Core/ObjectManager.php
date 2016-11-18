<?php
namespace Bitmotion\SecureDownloads\Core;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Bitmotion GmbH (typo3-ext@bitmotion.de)
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\SingletonInterface;

class ObjectManager implements SingletonInterface
{
    /**
     * Registered alternative implementations of a class
     * e.g. used to know the class for a AbstractClass or a Dependency
     *
     * @var array
     */
    private $alternativeImplementation = array();

    /**
     * register a classname that should be used if a dependency is required.
     * e.g. used to define default class for a interface
     *
     * @param string $className
     * @param string $alternativeClassName
     *
     * @throws \InvalidArgumentException
     */
    public function registerImplementation($className, $alternativeClassName)
    {
        if (!array_key_exists($className, $this->alternativeImplementation)) {
            if (interface_exists($className)) {
                throw new \InvalidArgumentException('Cannot register implementation for Interfaces', 1378921763);
            }
            $this->alternativeImplementation[$className] = $alternativeClassName;
            class_alias($alternativeClassName, $className);
        } else {
            // Ignore this error
        }
    }

}