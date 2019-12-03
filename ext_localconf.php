<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        // Load libraries when TYPO3 is not in composer mode
        if (!defined('TYPO3_COMPOSER_MODE') || !TYPO3_COMPOSER_MODE) {
            require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey) . 'Libraries/vendor/autoload.php';
        }

        // Load extension configuration
        $configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration::class);

        // Register eID script
        // TODO: This part is deprecated and will be removed with version 5
        $GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['tx_securedownloads'] = 'EXT:secure_downloads/Resources/Private/Scripts/FileDeliveryEidDispatcher.php';

        // Register hooks
        // TODO: This part is deprecated and will be removed with version 5
        if (!empty($configuration->getDomain())) {
            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = \Bitmotion\SecureDownloads\Service\SecureDownloadService::class . '->parseFE';
        }

        // Connect to signal slots
        // TODO: Remove this when dropping TYPO3 9 LTS support.
        if (version_compare(TYPO3_version, '10.0.0', '<')) {
            $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
            $signalSlotDispatcher->connect(
                \TYPO3\CMS\Core\Resource\ResourceStorage::class,
                \TYPO3\CMS\Core\Resource\ResourceStorage::SIGNAL_PreGeneratePublicUrl,
                \Bitmotion\SecureDownloads\Signal::class,
                'getPublicUrl'
            );
        }

        // Add link prefix to additionalAbsRefPrefixDirectories
        $GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories'] .= sprintf(',%s', $configuration->getLinkPrefix());

    }, 'secure_downloads'
);


