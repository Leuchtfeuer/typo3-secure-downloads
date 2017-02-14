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
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * Class Statistic
 * @package Bitmotion\SecureDownloads\Domain\Model
 */
class Statistic
{
    /**
     * traffic
     *
     * @var float
     */
    protected $traffic = 0.00;

    /**
     * from
     *
     * @var \DateTime
     */
    protected $from = null;

    /**
     * till
     *
     * @var \DateTime
     */
    protected $till = null;

    /**
     * Statistic constructor.
     *
     * @param QueryResultInterface $logEntries
     */
    public function __construct($logEntries)
    {
        $this->from = new \DateTime();
        $this->till = new \DateTime();
        $count = $logEntries->count();

        if ($count > 0) {
            $this->till->setTimestamp($logEntries->getFirst()->getTstamp());
            $i = 1;

            /** @var Log $logEntry */
            foreach ($logEntries as $logEntry) {
                $this->traffic += $logEntry->getFileSize();
                if ($i === $count) {
                    $this->from->setTimestamp($logEntry->getTstamp());
                }
                $i++;
            }
        }
    }

    /**
     * @return float
     */
    public function getTraffic()
    {
        return $this->traffic;
    }

    /**
     * @return \DateTime
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @return \DateTime
     */
    public function getTill()
    {
        return $this->till;
    }

}