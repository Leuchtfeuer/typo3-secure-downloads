<?php

declare(strict_types=1);

/*
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\SecureDownloads\Domain\Repository;

use Doctrine\DBAL\Exception;
use Doctrine\DBAL\ParameterType;
use Leuchtfeuer\SecureDownloads\Resource\Driver\SecureDownloadsDriver;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class StorageRepository extends \TYPO3\CMS\Core\Resource\StorageRepository
{
    /**
     * Creates the "Secure Downloads" file storage object if not exists and if the extension configuration option is enabled. This
     * method will also create the directory containing the assets and puts an .htaccess file into that directory.
     */
    public function createSecureDownloadStorage(): void
    {
        $path = sprintf('%s/%s', Environment::getPublicPath(), SecureDownloadsDriver::BASE_PATH);

        if (!@is_dir($path)) {
            GeneralUtility::mkdir($path);
            $this->addHtaccessFile($path);
        }

        if (!$this->isStorageDriverExisting()) {
            $this->createLocalStorage(
                'Secure Downloads (auto-created)',
                SecureDownloadsDriver::BASE_PATH,
                'relative',
                'This is the local "Secure Downloads" directory. All contained files are protected against direct access.',
                false
            );

            $this->initializeLocalCache();
        }
    }

    /**
     * Creates the database record and modifies it by defined values. It reverts the public availability of the storage and adapts
     * the driver.
     *
     * @param string $name        The name of the storage
     * @param string $basePath    The base path of storage
     * @param string $pathType    The path type of the storage. One of "relative" or "absolute"
     * @param string $description The description of the storage
     * @param bool   $default     Whether to set to default storage or not
     *
     * @return int id of the inserted record
     */
    public function createLocalStorage($name, $basePath, $pathType, $description = '', $default = false): int
    {
        $storageId = parent::createLocalStorage($name, $basePath, $pathType, $description, $default);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);
        $queryBuilder
            ->update($this->table)
            ->set('is_public', 0)
            ->set('driver', SecureDownloadsDriver::DRIVER_SHORT_NAME)
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($storageId, ParameterType::INTEGER)))
            ->executeStatement();

        return $storageId;
    }

    /**
     * Checks if a storage driver for secure downloads already exists
     *
     * @throws Exception
     */
    private function isStorageDriverExisting(): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->table);

        $result = $queryBuilder
            ->count('*')
            ->from($this->table)
            ->where($queryBuilder->expr()->eq('driver', $queryBuilder->createNamedParameter(SecureDownloadsDriver::DRIVER_SHORT_NAME)))
            ->executeQuery();

        return $result->fetchOne() > 0;
    }

    /**
     * Writes an .htaccess file into the "Secure Downloads" file storage.
     *
     * @param string $path Absolute path to the "Secure Downloads" file storage
     */
    private function addHtaccessFile(string $path): void
    {
        $fileLocation = sprintf('%s/.htaccess', rtrim($path, '/'));
        GeneralUtility::writeFile($fileLocation, $this->getHtaccessContent());
    }

    /**
     * @return string The generated .htaccess content
     */
    private function getHtaccessContent(): string
    {
        return <<<htaccess
<IfModule mod_authz_core.c>
  Require all denied
</IfModule>

<IfModule !mod_authz_core.c>
  Order Allow,Deny
  Deny from all
</IfModule>
htaccess;
    }
}
