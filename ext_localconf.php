<?php
if (!defined ("TYPO3_MODE")) {
	die ("Access denied.");
}

/////////////////////////////////////
// Version specific initialisation //
/////////////////////////////////////
if (substr(TYPO3_branch, 0, 1) === '4') {
	// Compatibility class loader which does class_alias magic and loads classes by naming convention
	require_once t3lib_extMgm::extPath($_EXTKEY) . 'Classes/Core/ClassLoader.php';
	spl_autoload_register(array(new \Bitmotion\NawSecuredl\Core\ClassLoader(), 'loadClass'));
	// Compatibility mode for TYPO3 versions below 6.0 (nothing needed atm)
//	require_once(t3lib_extMgm::extPath($_EXTKEY) . 'Resources/Private/Scripts/Compatibility.php');
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

$configurationManager = new \Bitmotion\NawSecuredl\Configuration\ConfigurationManager();
$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Bitmotion\\NawSecuredl\\Core\\ObjectManager');

if ($configurationManager->getValue('apacheDelivery')) {
	$objectManager->registerImplementation('Bitmotion\\NawSecuredl\\Resource\\Publishing\\ResourcePublishingTarget', 'Bitmotion\\NawSecuredl\\Resource\\Publishing\\Apache2DeliveryProtectedResourcePublishingTarget');
	$objectManager->registerImplementation('Bitmotion\\NawSecuredl\\Security\\Authorization\\Resource\\AccessRestrictionPublisher', 'Bitmotion\\NawSecuredl\\Security\\Authorization\\Resource\\Apache2AccessRestrictionPublisher');
} else {
	// PHP delivery. If any other delivery strategy is implemented, the eID script registration can be omitted
	$TYPO3_CONF_VARS['FE']['eID_include']['tx_nawsecuredl'] = 'EXT:naw_securedl/Resources/Private/Scripts/FileDeliveryEidDispatcher.php';
	$objectManager->registerImplementation('Bitmotion\\NawSecuredl\\Resource\\Publishing\\ResourcePublishingTarget', 'Bitmotion\\NawSecuredl\\Resource\\Publishing\\PhpDeliveryProtectedResourcePublishingTarget');
}

if (!$configurationManager->getValue('apacheDelivery')) {
	// TODO: use a dedicated option to switch HTML parsing on or off?
	// We need to use a Tx_ prefixed class name here because callUserFunction used by hooks in older TYPO3 versions check for that.
	// This class name is automatically aliased by core > 6.x and by our compatibility layer (see above)
	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = ':&Tx_NawSecuredl_Service_SecureDownloadService->parseFE';
}

unset($configurationManager);
unset($objectManager);

?>