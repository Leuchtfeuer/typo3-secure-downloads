<?php

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']);
}

/** @var $fileDelivery \Bitmotion\NawSecuredl\Resource\FileDelivery */
$fileDelivery = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('tx_nawsecuredl_output');
$fileDelivery->deliver();
