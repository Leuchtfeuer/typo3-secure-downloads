<?php

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Driver\DriverRegistry;

defined('TYPO3') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        // Create resource storage
        if ((new \Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration())->isCreateFileStorage()) {
            $storageRepositoryClass = \Leuchtfeuer\SecureDownloads\Domain\Repository\StorageRepository::class;
            $eventDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\EventDispatcher\EventDispatcher::class);
            $connectionPool = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Database\ConnectionPool::class);
            $driverRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Driver\DriverRegistry::class);
            $flexFormTools = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class);
            $logger = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Psr\Log\NullLogger::class);
            $storageRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($storageRepositoryClass, $eventDispatcher, $connectionPool, $driverRegistry, $flexFormTools, $logger);
            $storageRepository->createSecureDownloadStorage();
        }
    }, 'secure_downloads'
);
