<?php
defined('TYPO3_MODE') || die('Access denied.');

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

        // Add MimeTypes
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'] += \Leuchtfeuer\SecureDownloads\MimeTypes::ADDITIONAL_MIME_TYPES;

    }, 'secure_downloads'
);


