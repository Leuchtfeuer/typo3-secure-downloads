<?php
defined('TYPO3_MODE') || die('Access denied.');


##############################
### General initialisation ###
##############################
$TYPO3_CONF_VARS['FE']['eID_include']['tx_securedownloads'] = 'EXT:secure_downloads/Resources/Private/Scripts/FileDeliveryEidDispatcher.php';

\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class)
    ->connect(
        \TYPO3\CMS\Core\Resource\ResourceStorage::class,
        \TYPO3\CMS\Core\Resource\ResourceStorage::SIGNAL_PreGeneratePublicUrl,
        \Bitmotion\SecureDownloads\Resource\UrlGenerationInterceptor::class,
        'getPublicUrl'
    );

$configurationManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Bitmotion\SecureDownloads\Configuration\ConfigurationManager::class);
$objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Bitmotion\SecureDownloads\Core\ObjectManager::class);

// Default publishing target is PHP delivery (we might possibly make that configurable somehow)
$publishingTarget = \Bitmotion\SecureDownloads\Resource\Publishing\PhpDeliveryProtectedResourcePublishingTarget::class;

if ($configurationManager->getValue('apacheDelivery')) {
    $objectManager->registerImplementation(
        'Bitmotion\\SecureDownloads\\Security\\Authorization\\Resource\\AccessRestrictionPublisher',
        \Bitmotion\SecureDownloads\Security\Authorization\Resource\Apache2AccessRestrictionPublisher::class
    );

    if (TYPO3_MODE === 'FE') {
        // Apache delivery. The eID script registration can be omitted
        // No it cannot, because we need that from backend context which generates URLs with eID script
        $publishingTarget = \Bitmotion\SecureDownloads\Resource\Publishing\Apache2DeliveryProtectedResourcePublishingTarget::class;
        $TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['checkDataSubmission']['secure_downloads_set_access_token_cookie'] = \Bitmotion\SecureDownloads\Security\Authorization\Resource\AccessTokenCookiePublisher::class;
    }

} else {
    $TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = ':&' . \Bitmotion\SecureDownloads\Service\SecureDownloadService::class . '->parseFE';
}

$objectManager->registerImplementation(
    'Bitmotion\\SecureDownloads\\Resource\\Publishing\\ResourcePublishingTarget',
    $publishingTarget
);

unset($configurationManager, $objectManager, $publishingTarget);