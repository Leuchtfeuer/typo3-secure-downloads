<?php
namespace Bitmotion\NawSecuredl\Core;


use TYPO3\CMS\Core\SingletonInterface;

class ObjectManager implements SingletonInterface {
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
	 * @throws \InvalidArgumentException
	 */
	public function registerImplementation($className, $alternativeClassName) {
		if (!array_key_exists($className, $this->alternativeImplementation)) {
			if (interface_exists($className)) {
				throw new \InvalidArgumentException('Cannot register implementation for Interfaces', 1378921763);
			}
			$this->alternativeImplementation[$className] = $alternativeClassName;
			class_alias($alternativeClassName, $className);
		} else {
			// Ignore this error, or throw Exception?
		}
	}

}