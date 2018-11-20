<?php
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['bitmotion']['secure_downloads']['output']) {
    /** @noinspection ALL */
    include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['bitmotion']['secure_downloads']['output']);
}

\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Bitmotion\SecureDownloads\Resource\FileDelivery::class)->deliver();
