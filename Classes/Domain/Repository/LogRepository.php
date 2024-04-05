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

use Leuchtfeuer\SecureDownloads\Domain\Model\Log;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Filter;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\AbstractToken;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Repository;

class LogRepository extends Repository
{
    public const TABLENAME = 'tx_securedownloads_domain_model_log';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly DataMapper $dataMapper
    ) {}

    public function createQueryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLENAME);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder
            ->from(self::TABLENAME)
            ->orderBy('tstamp', 'DESC');

        return $queryBuilder;
    }

    public function findByFilter(?Filter $filter, int $currentPage = 1, int $itemsPerPage = 20): array
    {
        $queryBuilder = $this->createQueryBuilder();

        $this->applyFilter($queryBuilder, $filter);

        $result = $queryBuilder
            ->select('*')
            ->setMaxResults($itemsPerPage)
            ->setFirstResult($itemsPerPage * ($currentPage - 1))
            ->executeQuery()
            ->fetchAllAssociative() ?? [];
        return $this->dataMapper->map(Log::class, $result);
    }

    public function countByFilter(?Filter $filter): int
    {
        $queryBuilder = $this->createQueryBuilder();

        $this->applyFilter($queryBuilder, $filter);

        return (int)($queryBuilder
            ->count('uid')
            ->executeQuery()
            ->fetchOne() ?? 0);
    }

    public function getFirstTimestampByFilter(?Filter $filter, bool $reverse = false): int
    {
        $queryBuilder = $this->createQueryBuilder();

        $this->applyFilter($queryBuilder, $filter);

        return (int)($queryBuilder
            ->select('tstamp')
            ->orderBy('tstamp', $reverse ? 'DESC' : 'ASC')
            ->executeQuery()
            ->fetchOne() ?? 0);
    }

    public function getTrafficSumByFilter(?Filter $filter): float
    {
        $queryBuilder = $this->createQueryBuilder();

        $this->applyFilter($queryBuilder, $filter);

        return (float)($queryBuilder
            ->selectLiteral('SUM(file_size) AS sum')
            ->executeQuery()
            ->fetchOne() ?? 0.0);
    }

    protected function applyFilter(QueryBuilder &$queryBuilder, Filter $filter): void
    {
        $constraints = [];

        if ($filter instanceof Filter) {
            try {
                // FileType
                $this->applyFileTypePropertyToFilter($filter->getFileType(), $queryBuilder, $constraints);

                // User Type
                $this->applyUserTypePropertyToFilter($filter, $queryBuilder, $constraints);

                // Period
                $this->applyPeriodPropertyToFilter($filter, $queryBuilder, $constraints);

                // User and Page
                $this->applyEqualPropertyToFilter((int)$filter->getFeUserId(), 'user', $queryBuilder, $constraints);
                $this->applyEqualPropertyToFilter((int)$filter->getPageId(), 'page', $queryBuilder, $constraints);

                if (count($constraints) > 0) {
                    $queryBuilder->where(...$constraints);
                }
            } catch (InvalidQueryException $exception) {
                // Do nothing for now.
            }
        }
    }

    protected function applyFileTypePropertyToFilter(string $fileType, QueryBuilder $queryBuilder, array &$constraints): void
    {
        if ($fileType !== '' && $fileType !== '0') {
            $constraints[] = $queryBuilder->expr()->eq('media_type', $queryBuilder->createNamedParameter($fileType));
        }
    }

    protected function applyUserTypePropertyToFilter(Filter $filter, QueryBuilder $queryBuilder, array &$constraints): void
    {
        if ($filter->getUserType() === Filter::USER_TYPE_LOGGED_ON) {
            $constraints[] = $queryBuilder->expr()->gt('user', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT));
        }
        if ($filter->getUserType() === Filter::USER_TYPE_LOGGED_OFF) {
            $constraints[] = $queryBuilder->expr()->eq('user', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT));
        }
    }

    protected function applyPeriodPropertyToFilter(Filter $filter, QueryBuilder $queryBuilder, array &$constraints): void
    {
        if ((int)$filter->getFrom() !== 0) {
            $constraints[] = $queryBuilder->expr()->gte('tstamp', $queryBuilder->createNamedParameter($filter->getFrom(), Connection::PARAM_INT));
        }

        if ((int)$filter->getTill() !== 0) {
            $constraints[] = $queryBuilder->expr()->lte('tstamp', $queryBuilder->createNamedParameter($filter->getTill(), Connection::PARAM_INT));
        }
    }

    protected function applyEqualPropertyToFilter(int $property, string $propertyName, QueryBuilder $queryBuilder, array &$constraints): void
    {
        if ($property !== 0) {
            $constraints[] = $queryBuilder->expr()->eq($propertyName, $queryBuilder->createNamedParameter($property, Connection::PARAM_INT));
        }
    }

    /**
     * Creates a log entry in the database.
     *
     * @param AbstractToken $token The token containing information that should be logged
     * @param int $fileSize The file size of the file that should be logged
     * @param string $mimeType The mime type of the file that should be logged
     * @param int $user The ID of the user that downloaded the file
     * @throws ResourceDoesNotExistException
     */
    public function logDownload(AbstractToken $token, int $fileSize, string $mimeType, int $user): void
    {
        $pathInfo = pathinfo($token->getFile());

        $log = new Log();
        $log->setFileSize($fileSize);
        $log->setFilePath($pathInfo['dirname'] . '/' . $pathInfo['filename']);
        $log->setFileType($pathInfo['extension']);
        $log->setFileName($pathInfo['filename']);
        $log->setMediaType($mimeType);
        $log->setUser($user);
        $log->setPage($token->getPage());

        $fileObject = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($token->getFile());

        if ($fileObject) {
            $log->setFileId((string)$fileObject->getUid());
        }

        $queryBuilder = $this->createQueryBuilder();
        $queryBuilder
            ->insert(self::TABLENAME)
            ->values($log->toArray())
            ->executeStatement();
    }
}
