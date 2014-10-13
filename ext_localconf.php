<?php
if (!defined ("TYPO3_MODE")) {
	die ("Access denied.");
}
/* ##############################
   ### General initialisation ###
   ############################## */
$TYPO3_CONF_VARS['FE']['eID_include']['tx_nawsecuredl'] = 'EXT:naw_securedl/Resources/Private/Scripts/FileDeliveryEidDispatcher.php';

/* #######################################
   ### Version specific initialisation ###
   ####################################### */
if (substr(TYPO3_branch, 0, 1) === '4') {
	// Compatibility class loader which does class_alias magic and loads classes by naming convention
	require_once t3lib_extMgm::extPath($_EXTKEY) . 'Classes/Core/ClassLoader.php';
	spl_autoload_register(array(new \Bitmotion\NawSecuredl\Core\ClassLoader(), 'loadClass'));
	// Compatibility mode for TYPO3 versions below 6.0 (nothing needed atm)
	// require_once(t3lib_extMgm::extPath($_EXTKEY) . 'Resources/Private/Scripts/Compatibility.php');
	// TYPO3 < 6.0
	// (would be ignored in higher versions, but since we need to differentiate anyway, we can only register for a specific branch to avoid clutter)
	$TYPO3_CONF_VARS['BE']['XCLASS']['typo3/class.file_list.inc'] = t3lib_extMgm::extPath($_EXTKEY) . 'Classes/Xclass/class.ux_fileList.inc';
	$TYPO3_CONF_VARS['FE']['XCLASS']['tslib/showpic.php'] = t3lib_extMgm::extPath($_EXTKEY) . 'Classes/Xclass/class.ux_SC_tslib_showpic.php';

	$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Bitmotion\\NawSecuredl\\Core\\ObjectManager');
	// No other options for 4.5
	// Keep the old behaviour! For new features use a new TYPO3 Version!
	$objectManager->registerImplementation('Bitmotion\\NawSecuredl\\Resource\\Publishing\\ResourcePublishingTarget', 'Bitmotion\\NawSecuredl\\Resource\\Publishing\\PhpDeliveryProtectedResourcePublishingTarget');
	$TYPO3_CONF_VARS['FE']['eID_include']['tx_nawsecuredl'] = 'EXT:naw_securedl/Resources/Private/Scripts/FileDeliveryEidDispatcher.php';
	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = ':&Tx_NawSecuredl_Service_SecureDownloadService->parseFE';

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
	// TODO: Remove this once the ShowImageController in TYPO3 is fixed to use FAL
	if ($_GET['eID'] === 'tx_cms_showpic') {
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Frontend\\Controller\\ShowImageController'] = array(
			'className' => 'Bitmotion\\NawSecuredl\\TYPO3\\CMS\\Controller\\ShowImageController',
		);
	}

	$configurationManager = new \Bitmotion\NawSecuredl\Configuration\ConfigurationManager();
	$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Bitmotion\\NawSecuredl\\Core\\ObjectManager');

	// Default publishing target is PHP delivery (we might possibly make that configurable somehow)
	$publishingTarget = 'Bitmotion\\NawSecuredl\\Resource\\Publishing\\PhpDeliveryProtectedResourcePublishingTarget';

	if ($configurationManager->getValue('apacheDelivery')) {
		$objectManager->registerImplementation('Bitmotion\\NawSecuredl\\Security\\Authorization\\Resource\\AccessRestrictionPublisher', 'Bitmotion\\NawSecuredl\\Security\\Authorization\\Resource\\Apache2AccessRestrictionPublisher');
		if (TYPO3_MODE === 'FE') {
			// Apache delivery. The eID script registration can be omitted
			// No it cannot, because we need that from backend context which generates URLs with eID script
//			unset($TYPO3_CONF_VARS['FE']['eID_include']['tx_nawsecuredl']);
			$publishingTarget = 'Bitmotion\\NawSecuredl\\Resource\\Publishing\\Apache2DeliveryProtectedResourcePublishingTarget';
			$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkDataSubmission']['naw_securedl_set_access_token_cookie'] = 'Bitmotion\NawSecuredl\Security\Authorization\Resource\\AccessTokenCookiePublisher';
		}
	} else {
		$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = ':&Tx_NawSecuredl_Service_SecureDownloadService->parseFE';
	}
	$objectManager->registerImplementation('Bitmotion\\NawSecuredl\\Resource\\Publishing\\ResourcePublishingTarget', $publishingTarget);
}

unset($configurationManager, $objectManager, $publishingTarget);