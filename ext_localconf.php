<?php
if (!defined("TYPO3_MODE")) {
    die ("Access denied.");
}

/* ##############################
   ### General initialisation ###
   ############################## */
$TYPO3_CONF_VARS['FE']['eID_include']['tx_securedownloads'] = 'EXT:secure_downloads/Resources/Private/Scripts/FileDeliveryEidDispatcher.php';


if (class_exists('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher')) {
    \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher')->connect(
        'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
        \TYPO3\CMS\Core\Resource\ResourceStorage::SIGNAL_PreGeneratePublicUrl,
        'Bitmotion\\SecureDownloads\\Resource\\UrlGenerationInterceptor',
        'getPublicUrl'
    );
}

$configurationManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Bitmotion\\SecureDownloads\\Configuration\\ConfigurationManager');

$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Bitmotion\\SecureDownloads\\Core\\ObjectManager');

// Default publishing target is PHP delivery (we might possibly make that configurable somehow)
$publishingTarget = 'Bitmotion\\SecureDownloads\\Resource\\Publishing\\PhpDeliveryProtectedResourcePublishingTarget';

if ($configurationManager->getValue('apacheDelivery')) {
    $objectManager->registerImplementation('Bitmotion\\SecureDownloads\\Security\\Authorization\\Resource\\AccessRestrictionPublisher',
        'Bitmotion\\SecureDownloads\\Security\\Authorization\\Resource\\Apache2AccessRestrictionPublisher');

    if (TYPO3_MODE === 'FE') {
        // Apache delivery. The eID script registration can be omitted
        // No it cannot, because we need that from backend context which generates URLs with eID script
        $publishingTarget = 'Bitmotion\\SecureDownloads\\Resource\\Publishing\\Apache2DeliveryProtectedResourcePublishingTarget';
        $TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkDataSubmission']['secure_downloads_set_access_token_cookie'] = 'Bitmotion\\SecureDownloads\\Security\\Authorization\\Resource\\AccessTokenCookiePublisher';
    }

} else {
    $TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'Bitmotion\\SecureDownloads\\Service\\SecureDownloadService' . '->parseFE';
}

$objectManager->registerImplementation('Bitmotion\\SecureDownloads\\Resource\\Publishing\\ResourcePublishingTarget',
    $publishingTarget);

unset($configurationManager, $objectManager, $publishingTarget);