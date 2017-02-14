<?php
namespace Bitmotion\SecureDownloads\Domain\Model;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2017 Bitmotion GmbH (typo3-ext@bitmotion.de)
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
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

/**
 * Filter
 */
class Filter
{
    const USER_TYPE_ALL = 0;
    const USER_TYPE_LOGGED_ON = -1;
    const USER_TYPE_LOGGED_OFF = -2;

    /**
     * fileType
     *
     * @var string
     */
    protected $fileType = '';

    /**
     * from
     *
     * @var string
     */
    protected $from = '';

    /**
     * till
     *
     * @var string
     */
    protected $till = '';

    /**
     * feUserId
     *
     * @var int
     */
    protected $feUserId = 0;

    /**
     * userType
     *
     * @var int
     */
    protected $userType = 0;

    /**
     * pageId
     *
     * @var int
     */
    protected $pageId = 0;

    /**
     * @return int
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     * @param int $pageId
     */
    public function setPageId($pageId)
    {
        $this->pageId = $pageId;
    }

    /**
     * @return string
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * @param string $fileType
     */
    public function setFileType($fileType)
    {
        $this->fileType = $fileType;
    }

    /**
     * @return string
     */
    public function getFrom()
    {
        return $this->formatDate($this->from);
    }

    /**
     * @param string $from
     */
    public function setFrom($from)
    {
        $this->from = $from;

    }

    /**
     * @param $dateString
     * @return string
     */
    private function formatDate($dateString)
    {
        if ($dateString == '') {
            return null;
        }

        list($date, $time) = explode(' ', trim($dateString));

        if ($date == '') {
            list($year, $month, $day) = explode('-', $dateString);
        } else {
            list($year, $month, $day) = explode('-', $date);
            list($hour, $minute, $second) = explode(':', $time);
        }

        $dateTime = new \DateTime();
        $dateTime->setDate($year, (int)$month, (int)$day);
        $dateTime->setTime((int)$hour, (int)$minute, (int)$second);

        return $dateTime->getTimestamp();
    }

    /**
     * @return string
     */
    public function getTill()
    {
        return $this->formatDate($this->till);
    }

    /**
     * @param string $till
     */
    public function setTill($till)
    {
        $this->till = $till;
    }

    /**
     * @return int
     */
    public function getFeUserId()
    {
        return $this->feUserId;
    }

    /**
     * @param int $feUserId
     */
    public function setFeUserId($feUserId)
    {
        switch ($feUserId) {
            case Filter::USER_TYPE_LOGGED_ON:
                $this->userType = Filter::USER_TYPE_LOGGED_ON;
                $this->feUserId = 0;
                break;

            case Filter::USER_TYPE_LOGGED_OFF:
                $this->userType = Filter::USER_TYPE_LOGGED_OFF;
                $this->feUserId = 0;
                break;

            default:
                $this->feUserId = $feUserId;
        }
    }

    /**
     * @return int
     */
    public function getUserType()
    {
        return $this->userType;
    }

    /**
     * @param int $userType
     */
    public function setUserType($userType)
    {
        $this->userType = $userType;
    }

}