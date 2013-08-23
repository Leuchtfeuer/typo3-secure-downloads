<?php
if (!defined ("TYPO3_MODE")) {
	die ("Access denied.");
}

/////////////////////////////////////
// Version specific initialisation //
/////////////////////////////////////
if (substr(TYPO3_branch, 0, 1) === '4') {
	// Compatibility mode for TYPO3 versions below 6.0
	require_once(t3lib_extMgm::extPath($_EXTKEY) . 'Compatibility/Compatibility.php');
	// TYPO3 < 6.0
	// (would be ignored in higher versions, but since we need to differentiate anyway, we can only register for a specific branch to avoid clutter)
	$TYPO3_CONF_VARS['BE']['XCLASS']['typo3/class.file_list.inc'] = t3lib_extMgm::extPath($_EXTKEY) . 'Classes/Xclass/class.ux_fileList.inc';
	$TYPO3_CONF_VARS['FE']['XCLASS']['tslib/showpic.php'] = t3lib_extMgm::extPath($_EXTKEY) . 'Classes/Xclass/class.ux_SC_tslib_showpic.php';
} else {
	// TYPO3 > 6.0
	if (class_exists('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher')) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher')->connect(
			'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
			\TYPO3\CMS\Core\Resource\ResourceStorage::SIGNAL_PreGeneratePublicUrl,
			'Bitmotion\\NawSecuredl\\Resource\\UrlGenerationInterceptor',
			'getPublicUrl'
		);
	}
}

///////////////////////////////////
///// General initialisation //////
///////////////////////////////////
// TODO: make configurable which "publishing strategy to use" (currently only the PhpDelivery target is implemented)
class_alias('Bitmotion\\NawSecuredl\\Resource\\Publishing\\PhpDeliveryProtectedResourcePublishingTarget', 'Bitmotion\\NawSecuredl\\Resource\\Publishing\\ResourcePublishingTarget');
// We need to use a Tx_ prefixed class name here because callUserFunction used by hooks in older TYPO3 versions check for that.
// This class name is automatically aliased by core > 6.x and by our compatibility layer (see above)
// TODO: make configurable if HTML parsing should be done or not
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = ':&Tx_NawSecuredl_Service_SecureDownloadService->parseFE';
// PHP delivery. If any other delivery strategy is implemented, the eID script registration can be omitted
$TYPO3_CONF_VARS['FE']['eID_include']['tx_nawsecuredl'] = 'EXT:naw_securedl/class.tx_nawsecuredl_output.php';
?>