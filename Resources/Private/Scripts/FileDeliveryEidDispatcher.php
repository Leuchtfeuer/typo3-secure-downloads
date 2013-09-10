<?php

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']);
}

// This has to be done here, as the class is only loaded before eID excution in 4.5
if (!class_exists('TYPO3\\CMS\\Frontend\\Utility\\EidUtility', FALSE)) {
	class_alias('tslib_eidtools', 'TYPO3\\CMS\\Frontend\\Utility\\EidUtility');
}

/** @var $fileDelivery \Bitmotion\NawSecuredl\Resource\FileDelivery */
$fileDelivery = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_nawsecuredl_output');
$fileDelivery->deliver();
