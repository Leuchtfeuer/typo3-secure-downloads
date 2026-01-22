<?php

use Leuchtfeuer\SecureDownloads\Domain\Repository\StorageRepository;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\EventDispatcher;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

defined('TYPO3') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        // Create resource storage
        if ((new ExtensionConfiguration())->isCreateFileStorage()) {
            $storageRepositoryClass = StorageRepository::class;
            $eventDispatcher = GeneralUtility::makeInstance(EventDispatcher::class);
            $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
            $driverRegistry = GeneralUtility::makeInstance(DriverRegistry::class);
            $flexFormTools = GeneralUtility::makeInstance(FlexFormTools::class);
            $logger = GeneralUtility::makeInstance(NullLogger::class);
            $storageRepository = GeneralUtility::makeInstance($storageRepositoryClass, $eventDispatcher, $connectionPool, $driverRegistry, $flexFormTools, $logger);
            $storageRepository->createSecureDownloadStorage();
        }
    }, 'secure_downloads'
);
