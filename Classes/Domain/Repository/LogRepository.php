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
 *  (c) 2019 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Leuchtfeuer\SecureDownloads\Domain\Model\Log;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Filter;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\AbstractToken;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class LogRepository extends Repository
{
    protected $defaultOrderings = [
        'tstamp' => QueryInterface::ORDER_DESCENDING,
    ];

    public function createQuery(): QueryInterface
    {
        $query = parent::createQuery();
        $querySettings = $query->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $querySettings->setRespectSysLanguage(false);
        $query->setQuerySettings($querySettings);

        return $query;
    }

    public function findByFilter(?Filter $filter): QueryResultInterface
    {
        $query = $this->createQuery();

        if ($filter instanceof Filter) {
            try {
                $this->applyFilter($query, $filter);
            } catch (InvalidQueryException $exception) {
                // Do nothing for now.
            }
        }

        return $query->execute();
    }

    /**
     * @throws InvalidQueryException
     */
    protected function applyFilter(QueryInterface &$query, Filter $filter): void
    {
        $constraints = [];

        // FileType
        $this->applyFileTypePropertyToFilter($filter->getFileType(), $query, $constraints);

        // User Type
        $this->applyUserTypePropertyToFilter($filter, $query, $constraints);

        // Period
        $this->applyPeriodPropertyToFilter($filter, $query, $constraints);

        // User and Page
        $this->applyEqualPropertyToFilter((int)$filter->getFeUserId(), 'user', $query, $constraints);
        $this->applyEqualPropertyToFilter((int)$filter->getPageId(), 'page', $query, $constraints);

        if (count($constraints) > 0) {
            $query->matching($query->logicalAnd($constraints));
        }
    }

    protected function applyFileTypePropertyToFilter($fileType, QueryInterface $query, array &$constraints): void
    {
        if ($fileType !== '' && $fileType !== '0') {
            $constraints[] = $query->equals('mediaType', $fileType);
        }
    }

    protected function applyUserTypePropertyToFilter(Filter $filter, QueryInterface $query, array &$constraints): void
    {
        if ($filter->getUserType() != 0) {
            $userQuery = $query->equals('user', null);

            if ($filter->getUserType() === Filter::USER_TYPE_LOGGED_ON) {
                $constraints[] = $query->logicalNot($userQuery);
            }
            if ($filter->getUserType() === Filter::USER_TYPE_LOGGED_OFF) {
                $constraints[] = $userQuery;
            }
        }
    }

    /**
     * @throws InvalidQueryException
     */
    protected function applyPeriodPropertyToFilter(Filter $filter, QueryInterface $query, array &$constraints): void
    {
        if ((int)$filter->getFrom() !== 0) {
            $constraints[] = $query->greaterThanOrEqual('tstamp', $filter->getFrom());
        }

        if ((int)$filter->getTill() !== 0) {
            $constraints[] = $query->lessThanOrEqual('tstamp', $filter->getTill());
        }
    }

    protected function applyEqualPropertyToFilter(int $property, string $propertyName, QueryInterface $query, array $constraints): void
    {
        if ($property !== 0) {
            $constraints[] = $query->equals($propertyName, $property);
        }
    }

    public function logDownload(AbstractToken $token, $fileSize, $mimeType, $user): void
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

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_securedownloads_domain_model_log');
        $queryBuilder->insert('tx_securedownloads_domain_model_log')->values($log->toArray())->execute();
    }
}
