<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        // Load libraries when TYPO3 is not in composer mode
        if (!defined('TYPO3_COMPOSER_MODE') || !TYPO3_COMPOSER_MODE) {
            require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey) . 'Libraries/vendor/autoload.php';
        }

        // Register eID script
        // TODO: This part is deprecated and will be removed with version 5
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
            \Bitmotion\SecureDownloads\Signal::class,
            'getPublicUrl'
        );

        // Add link prefix to additionalAbsRefPrefixDirectories
        $configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration::class);
        $GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories'] .= sprintf(',%s', $configuration->getLinkPrefix());

    }, 'secure_downloads'
);


