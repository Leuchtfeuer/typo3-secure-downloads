<?php

class_alias('t3lib_div', 'TYPO3\\CMS\\Core\\Utility\\GeneralUtility');
class_alias('t3lib_Singleton', 'TYPO3\\CMS\\Core\\SingletonInterface');
//class_alias('tslib_eidtools', 'TYPO3\\CMS\\Frontend\\Utility\\EidUtility');

require_once __DIR__ . '/../../../Classes/Core/ClassLoader.php';
spl_autoload_register(array(new \Bitmotion\NawSecuredl\Core\ClassLoader(), 'loadClass'));

class_alias('Bitmotion\\NawSecuredl\\Compatibility\\MathUtility', 'TYPO3\\CMS\\Core\\Utility\\MathUtility');
