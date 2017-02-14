<?php
namespace Bitmotion\SecureDownloads\Domain\Repository;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Bitmotion GmbH (typo3-ext@bitmotion.de)
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use Bitmotion\SecureDownloads\Domain\Model\Filter;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Class LogRepository
 * @package Bitmotion\SecureDownloads\Domain\Repository
 */
class LogRepository extends Repository
{

    protected $defaultOrderings = [
        'tstamp' => QueryInterface::ORDER_DESCENDING,
    ];

    public function initializeObject()
    {
        /** @var Typo3QuerySettings $querySettings */
        $querySettings = $this->objectManager->get(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $querySettings->setRespectSysLanguage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * @param null|Filter $filter
     * @return array|QueryResultInterface
     */
    public function findByFilter($filter)
    {
        $query = $this->createQuery();

        if ($filter instanceof Filter) {
            $constraints = [];

            // FileType
            if ($filter->getFileType() !== '' && $filter->getFileType() !== '0') {
                $constraints[] = $query->equals('mediaType', $filter->getFileType());
            }

            // User Type
            if ($filter->getUserType() != 0) {
                $userQuery = $query->equals('user', null);

                if ($filter->getUserType() === Filter::USER_TYPE_LOGGED_ON) {
                    $constraints[] = $query->logicalNot($userQuery);
                }
                if ($filter->getUserType() === Filter::USER_TYPE_LOGGED_OFF) {
                    $constraints[] = $userQuery;
                }
            }

            // User
            if ($filter->getFeUserId() !== 0) {
                $constraints[] = $query->equals('user', $filter->getFeUserId());
            }

            // Timeframe
            if ($filter->getFrom() !== '' && $filter->getFrom() !== null) {
                $constraints[] = $query->greaterThanOrEqual('tstamp', $filter->getFrom());
            }

            if ($filter->getTill() !== '' && $filter->getTill() !== null) {
                $constraints[] = $query->lessThanOrEqual('tstamp', $filter->getTill());
            }

            // Page
            if ($filter->getPageId() !== 0) {
                $constraints[] = $query->equals('page', $filter->getPageId());
            }

            if (count($constraints) > 0) {
                $query->matching($query->logicalAnd($constraints));
            }
        }

        return $query->execute();
    }
}

