<?php

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['bitmotion']['secure_downloads']['output']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['bitmotion']['secure_downloads']['output']);
}

/** @var $fileDelivery \Bitmotion\SecureDownloads\Resource\FileDelivery */
$fileDelivery = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Bitmotion\\SecureDownloads\\Resource\\FileDelivery');
$fileDelivery->deliver();
