<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        // Load extension configuration
        $configuration = new \Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration();

        // Define some variables depending on TYPO3 Version
        // TODO: Remove this when dropping TYPO3 9 LTS support.
        if (version_compare(TYPO3_version, '10.0.0', '>=')) {
            $extensionName = $extensionKey;
            $controllerName = \Bitmotion\SecureDownloads\Controller\LogController::class;
        } else {
            $extensionName = 'Bitmotion.' . $extensionKey;
            $controllerName = 'Log';
        }

        // Add overlay for file list icons if file or folder is secured
        // TODO: Remove this when dropping TYPO3 9 LTS support.
        if (version_compare(TYPO3_version, '10.0.0', '<')) {
            $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
            $signalSlotDispatcher->connect(
                \TYPO3\CMS\Core\Imaging\IconFactory::class,
                'buildIconForResourceSignal',
                \Bitmotion\SecureDownloads\Signal::class,
                'buildIconForResourceSignal'
            );
        }

        // Register the backend module if the log option is set in extension configuration
        if ($configuration->isLog()) {
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                $extensionName,
                'web',
                'TrafficLog',
                '10',
                [
                    $controllerName => 'show,list',
                ], [
                    'access' => 'user,group',
                    'icon' => 'EXT:secure_downloads/Resources/Public/Icons/Extension.svg',
                    'labels' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_log.xlf',
                ]
            );
        }
    }, 'secure_downloads'
);
