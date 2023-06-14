<?php
defined('TYPO3') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        // Register the backend module if the log option is set in extension configuration
        if ((new \Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration())->isLog()) {
            \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
                $extensionKey,
                'web',
                'TrafficLog',
                '10',
                [
                    \Leuchtfeuer\SecureDownloads\Controller\LogController::class => 'show,list',
                ], [
                    'access' => 'user,group',
                    'icon' => 'EXT:secure_downloads/Resources/Public/Icons/Extension.svg',
                    'labels' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_log.xlf',
                ]
            );
        }

        // Create resource storage
        if ((new \Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration())->isCreateFileStorage()) {
            $storageRepositoryClass = \Leuchtfeuer\SecureDownloads\Domain\Repository\StorageRepository::class;
            $eventDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\EventDispatcher\EventDispatcher::class);
            $driverRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Driver\DriverRegistry::class);
            $storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($storageRepositoryClass, $eventDispatcher, $driverRegistry);
            $storageRepository->createSecureDownloadStorage();
        }
    }, 'secure_downloads'
);
