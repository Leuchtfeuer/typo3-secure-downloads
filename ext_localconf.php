<?php

use Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\DefaultToken;
use Leuchtfeuer\SecureDownloads\Registry\CheckRegistry;
use Leuchtfeuer\SecureDownloads\Registry\TokenRegistry;
use Leuchtfeuer\SecureDownloads\Resource\Driver\SecureDownloadsDriver;
use Leuchtfeuer\SecureDownloads\Security\UserCheck;
use Leuchtfeuer\SecureDownloads\Security\UserGroupCheck;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        // Load libraries when TYPO3 is not in composer mode
        if (Environment::isComposerMode() === false) {
            require ExtensionManagementUtility::extPath($extensionKey) . 'Libraries/vendor/autoload.php';
        }

        ##################
        #   FAL DRIVER   #
        ##################
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['registeredDrivers']['sdl'] =[
            'class' => SecureDownloadsDriver::class,
            'shortName' => SecureDownloadsDriver::DRIVER_SHORT_NAME,
            'flexFormDS' => 'FILE:EXT:secure_downloads/Configuration/Resource/Driver/SecureDownloadsDriverFlexForm.xml',
            'label' => SecureDownloadsDriver::DRIVER_NAME,
        ];

        // Register default token
        TokenRegistry::register(
            'tx_securedownloads_default',
            DefaultToken::class,
            0,
            false
        );

        // Register default checks
        CheckRegistry::register(
            'tx_securedownloads_group',
            UserGroupCheck::class,
            10,
            true
        );

        CheckRegistry::register(
            'tx_securedownloads_user',
            UserCheck::class,
            20,
            true
        );

    }, 'secure_downloads'
);


