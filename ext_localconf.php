<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        // Register eID script
        $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_securedownloads'] = 'EXT:secure_downloads/Resources/Private/Scripts/FileDeliveryEidDispatcher.php';

        // Register hooks
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = \Bitmotion\SecureDownloads\Service\SecureDownloadService::class . '->parseFE';

        // Default publishing target is PHP delivery (we might possibly make that configurable somehow)
        $objectManager = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Bitmotion\SecureDownloads\Core\ObjectManager::class);
        $objectManager->registerImplementation(
            'Bitmotion\\SecureDownloads\\Resource\\Publishing\\ResourcePublishingTarget',
            \Bitmotion\SecureDownloads\Resource\Publishing\PhpDeliveryProtectedResourcePublishingTarget::class
        );

        // Connect to signal slots
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
        $signalSlotDispatcher->connect(
            \TYPO3\CMS\Core\Resource\ResourceStorage::class,
            \TYPO3\CMS\Core\Resource\ResourceStorage::SIGNAL_PreGeneratePublicUrl,
            \Bitmotion\SecureDownloads\Resource\UrlGenerationInterceptor::class,
            'getPublicUrl'
        );

    }, 'secure_downloads'
);


