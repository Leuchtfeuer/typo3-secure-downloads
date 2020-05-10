<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Domain\Repository;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Leuchtfeuer\SecureDownloads\Resource\Driver\SecureDownloadsDriver;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class StorageRepository extends \TYPO3\CMS\Core\Resource\StorageRepository
{
    public function createSecureDownloadStorage()
    {
        $path = sprintf('%s/%s', Environment::getPublicPath(), SecureDownloadsDriver::BASE_PATH);

        if (!@is_dir($path)) {
            GeneralUtility::mkdir($path);
        }

        $storageObjects = $this->findByStorageType(SecureDownloadsDriver::DRIVER_SHORT_NAME);

        if (count($storageObjects) === 0) {
            $this->createLocalStorage(
                'Secure Downloads (auto-created)',
                \Leuchtfeuer\SecureDownloads\Resource\Driver\SecureDownloadsDriver::BASE_PATH,
                'relative',
                'This is the local "Secure Downloads" directory. All contained files are protected against direct access.',
                false
            );

            $this->initializeLocalCache();
        }
    }

    public function createLocalStorage($name, $basePath, $pathType, $description = '', $default = false)
    {
        $storageId = parent::createLocalStorage($name, $basePath, $pathType, $description, $default);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
        $queryBuilder
            ->update($this->table)
            ->set('is_public', 0)
            ->set('driver', SecureDownloadsDriver::DRIVER_SHORT_NAME)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($storageId, \PDO::PARAM_INT)))
            ->execute();

        return $storageId;
    }

    public function findByStorageType($storageType)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);

        return $queryBuilder
            ->select('*')
            ->from($this->table)
            ->where($queryBuilder->expr()->eq('driver', $queryBuilder->createNamedParameter(SecureDownloadsDriver::DRIVER_SHORT_NAME)))
            ->execute()
            ->fetchAll();
    }
}
