<?php
defined('TYPO3') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
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
