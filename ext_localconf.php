<?php
defined('TYPO3') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        // Load libraries when TYPO3 is not in composer mode
        if (\TYPO3\CMS\Core\Core\Environment::isComposerMode() === false) {
            require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey) . 'Libraries/vendor/autoload.php';
        }

        // Load extension configuration and add link prefix to additionalAbsRefPrefixDirectories
        $GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories'] .= sprintf(
            ',%s',
            (new \Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration())->getLinkPrefix()
        );

        ##################
        #   FAL DRIVER   #
        ##################
        $driverRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Driver\DriverRegistry::class);
        $driverRegistry->registerDriverClass(
            \Leuchtfeuer\SecureDownloads\Resource\Driver\SecureDownloadsDriver::class,
            \Leuchtfeuer\SecureDownloads\Resource\Driver\SecureDownloadsDriver::DRIVER_SHORT_NAME,
            \Leuchtfeuer\SecureDownloads\Resource\Driver\SecureDownloadsDriver::DRIVER_NAME,
            'FILE:EXT:secure_downloads/Configuration/Resource/Driver/SecureDownloadsDriverFlexForm.xml'
        );

        // Register default token
        \Leuchtfeuer\SecureDownloads\Registry\TokenRegistry::register(
            'tx_securedownloads_default',
            \Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\DefaultToken::class,
            0,
            false
        );

        // Register default checks
        \Leuchtfeuer\SecureDownloads\Registry\CheckRegistry::register(
            'tx_securedownloads_group',
            \Leuchtfeuer\SecureDownloads\Security\UserGroupCheck::class,
            10,
            true
        );

        \Leuchtfeuer\SecureDownloads\Registry\CheckRegistry::register(
            'tx_securedownloads_user',
            \Leuchtfeuer\SecureDownloads\Security\UserCheck::class,
            20,
            true
        );

        // Scheduler task
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][\TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask::class]['options']['tables']['tx_securedownloads_domain_model_log'] = [
            'dateField' => 'tstamp',
            'expirePeriod' => '180'
        ];

    }, 'secure_downloads'
);


