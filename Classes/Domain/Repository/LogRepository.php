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

use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

class LogRepository extends Repository
{

    protected $defaultOrderings = array(
        'tstamp' => QueryInterface::ORDER_DESCENDING
    );

    public function initializeObject()
    {
        /** @var Typo3QuerySettings $querySettings*/
        $querySettings = $this->objectManager->get(Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $querySettings->setRespectSysLanguage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * @param $filter
     *
     * @return QueryResultInterface
     */
    public function findFiltered($filter)
    {
        $query = $this->createQuery();

        $conditions = array();

        if (array_key_exists('mode', $filter) && $filter['mode'] === 'singlePage') {
            $conditions[] = $query->equals('page', $filter['page']);
        }

        if (array_key_exists('user', $filter) && !empty($filter['user'])) {
            switch ($filter['user']) {
                case -2:
                    $conditions[] = $query->equals('user', null);
                    break;
                case -1:
                    $conditions[] = $query->logicalNot($query->equals('user', null));
                    break;
                default:
                    $conditions[] = $query->equals('user', $filter['user']);
            }
        }

        if (array_key_exists('fileType', $filter) && !empty($filter['fileType'])) {
            $conditions[] = $query->equals('mediaType', $filter['fileType']);
        }

        if (array_key_exists('fromTstamp', $filter) && !empty($filter['fromTstamp'])) {
            $conditions[] = $query->greaterThanOrEqual('tstamp', $filter['fromTstamp']);
        }
        if (array_key_exists('tillTstamp', $filter) && !empty($filter['tillTstamp'])) {
            $conditions[] = $query->lessThanOrEqual('tstamp', $filter['tillTstamp']);
        }

        if (count($conditions) > 0) {
            $query->matching($query->logicalAnd($conditions));
        }

        return $query->execute();
    }
}

