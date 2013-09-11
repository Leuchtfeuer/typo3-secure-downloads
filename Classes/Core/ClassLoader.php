<?php
namespace Bitmotion\NawSecuredl\Core;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Helmut Hummel (helmut.hummel@typo3.org)
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
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

/**
 * Simple class loader that can load PSR-0 compatible classes of one extension
 */
class ClassLoader {

	/**
	 * Path to extension classes
	 *
	 * @var string
	 */
	private $classPath = '';

	/**
	 * Alias map for old class names
	 *
	 * @var array
	 */
	private $classAliasMap = array();

	/**
	 * Initialize class loader
	 */
	public function __construct() {
		$this->classPath = str_replace('\\', '/', dirname(__DIR__)) . '/';
		$this->loadAliasMap();
	}

	/**
	 * Simple class loader that can load PSR-0 compatible classes of one extension.
	 * It also takes care of aliasing old class names with namespaced ones,
	 * if found in the class alias map.
	 *
	 * @param string $className Class name of classes not loaded by the core class loader
	 * @return bool
	 */
	public function loadClass($className) {
		if (array_key_exists($className, $this->classAliasMap)) {
			class_alias($this->classAliasMap[$className], $className);
			return TRUE;
		}
		list($vendorName, $extensionName, $classPathPart) = explode('\\', $className, 3);
		$classPath = $this->classPath . str_replace('\\', '/', $classPathPart) . '.php';
		if ($vendorName === 'Bitmotion' && $extensionName === 'NawSecuredl' && @file_exists($classPath)) {
			require_once $classPath;
			return TRUE;
		} else {
			return FALSE;
		}
	}

	/**
	 * Loads the alias map and stores it in a member variable
	 */
	private function loadAliasMap() {
		$this->classAliasMap = require $this->classPath . '../Migrations/Code/ClassAliasMap.php';
		$this->classAliasMap = array_merge($this->classAliasMap, require $this->classPath . '../Migrations/Code/CompatibilityClassAliasMap.php');
	}
}
