<?php
if (!defined ("TYPO3_MODE")) {
	die ("Access denied.");
}

$TYPO3_CONF_VARS['FE']['eID_include']['tx_nawsecuredl'] = 'EXT:naw_securedl/class.tx_nawsecuredl_output.php';
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = ':&Tx_NawSecuredl_Service_SecureDownloadService->parseFE';

# TYPO3 > 6.0 (will be ignored in lower versions)
if (class_exists('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher')) {
	\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher')->connect(
		'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
		\TYPO3\CMS\Core\Resource\ResourceStorage::SIGNAL_PreGeneratePublicUrl,
		'Bitmotion\\NawSecuredl\\Resource\\UrlGenerationInterceptor',
		'getPublicUrl'
	);
}

// TODO: make configurable
class_alias('Bitmotion\\NawSecuredl\\Resource\\Publishing\\PhpDeliveryProtectedResourcePublishingTarget', 'Bitmotion\\NawSecuredl\\Resource\\Publishing\\ResourcePublishingTarget');


# TYPO3 < 6.0 (will be ignored in higher versions)
$TYPO3_CONF_VARS['BE']['XCLASS']['typo3/class.file_list.inc'] = t3lib_extMgm::extPath($_EXTKEY) . 'Classes/Xclass/class.ux_fileList.inc';
$TYPO3_CONF_VARS['FE']['XCLASS']['tslib/showpic.php'] = t3lib_extMgm::extPath($_EXTKEY) . 'Classes/Xclass/class.ux_SC_tslib_showpic.php';
?>