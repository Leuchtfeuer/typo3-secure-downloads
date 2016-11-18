<?php
namespace Bitmotion\SecureDownloads\Controller;

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
use Bitmotion\SecureDownloads\Domain\Model\Log;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * LogController
 */
class LogController extends ActionController
{

    /**
     * logRepository
     *
     * @var \Bitmotion\SecureDownloads\Domain\Repository\LogRepository
     * @inject
     */
    protected $logRepository = NULL;

    /**
     * pageRepository
     *
     * @var \TYPO3\CMS\Frontend\Page\PageRepository
     * @inject
     */
    protected $pageRepository = NULL;

    /**
     * action list
     *
     * @return void
     */
    public function listAction()
    {
        $pageId = (int)GeneralUtility::_GP('id');

        $mode = $this->getMode($pageId);

        $filter = $this->handleFilter($mode, $pageId);

        $logs = $this->logRepository->findFiltered($filter);

        $this->view->assignMultiple(array(
            'logs' => $logs,
            'mode' => $mode,
            'page' => $this->getPageObject($pageId),
            'users' => $this->getUsers(),
            'fileTypes' => $this->getFileTypes(),
            'filter' => $filter,
            'stat' => $this->getStatistic($logs),
        ));
    }

    /**
     * @param int $pageId
     *
     * @return string
     */
    private function getMode($pageId)
    {
        if ($pageId === 0) {
            return 'overview';
        } elseif ($this->request->hasArgument('mode') && !empty($this->request->getArgument('mode'))) {
            return $this->request->getArgument('mode');
        } else {
            return 'singlePage';
        }
    }

    /**
     * @param int $pageId
     *
     * @return array|null
     */
    private function getPageObject($pageId)
    {
        if (!empty($pageId) && $pageId !== 0) {
            return $this->pageRepository->getPage($pageId);
        }
        return null;
    }

    /**
     * @return array
     */
    private function getUsers()
    {
        $users = array();

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('user','tx_securedownloads_domain_model_log','user != 0','user');
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $getUserRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*','fe_users','uid = ' . $row['user']);
            $users[] = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($getUserRes);
        }

        return $users;
    }

    /**
     * @return array
     */
    private function getFileTypes()
    {
        $fileTypes = array();

        $res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('media_type','tx_securedownloads_domain_model_log','','media_type','media_type ASC');
        while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
            $fileTypes[] = array('title' => $row['media_type']);
        }

        return $fileTypes;
    }

    /**
     * @param string $mode
     * @param int $pageId
     *
     * @return array
     */
    private function handleFilter($mode, $pageId)
    {
        $filter = array('mode' => $mode, 'page' => $pageId);

        if ($this->request->hasArgument('filter')) {

            $postFilter = $this->request->getArgument('filter');
            $pattern = '/^((([0-9]{2})|([0-9]{4}))-([0-3]{1})?[0-9]{1}-([01]{1})?[0-9]{1})\s?((([0-2]{1})?[0-9]{1}(:([0-5]{1})?[0-9]{1}(:([0-5]{1})?[0-9]{1})?)?)?)$/';

            if (array_key_exists('from',$postFilter) && !preg_match($pattern, $postFilter['from'])) {
                $filter['from'] = '';
            } else {
                $filter['fromTstamp'] = $this->getTimestamp($postFilter['from']);
            }

            if (array_key_exists('till',$postFilter) && !preg_match($pattern, $postFilter['till'])) {
                $filter['till'] = '';
            } else {
                $filter['tillTstamp'] = $this->getTimestamp($postFilter['till']);
            }

            return array_merge($postFilter, $filter);
        }

        return $filter;
    }

    /**
     * @param string $timeString
     *
     * @return int
     */
    private function getTimestamp($timeString)
    {
        list($date, $time) = explode(' ',$timeString);

        if (!empty($time)) {
            $till = new \DateTime($date . 'T' . $time);
        } elseif (!empty($date)) {
            $till = new \DateTime($date . 'T00:00:00');
        } else {
            $till = new \DateTime($timeString);
        }
        return $till->getTimestamp();
    }

    /**
     * @param QueryResultInterface $logs
     *
     * @return array
     */
    private function getStatistic($logs)
    {
        $sum = 0;
        $from = time();
        $till = 0;

        $count = $logs->count();

        if ($count > 0) {
            $till = $logs->getFirst()->getTstamp();
            $i = 1;

            /** @var Log $log */
            foreach ($logs as $log) {
                $sum += $log->getFileSize();
                if ($i === $count) {
                    $from = $log->getTstamp();
                }
                $i++;
            }
        }

        return array(
            'trafficSum' => $sum,
            'from' => $from,
            'till' => $till,
        );
    }

}