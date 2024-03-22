<?php

declare(strict_types=1);
namespace Leuchtfeuer\SecureDownloads\Domain\Transfer;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Leuchtfeuer\SecureDownloads\Domain\Repository\LogRepository;

class Statistic
{
    public function __construct(
        protected \DateTime $from = new \DateTime(),
        protected \DateTime $till = new \DateTime(),
        protected float $traffic = 0.00
    )
    {
    }

    public function calc(Filter $filter, LogRepository $logRepository): void
    {
        if ($filter->getFrom() !== null) {
            $this->from->setTimestamp($filter->getFrom());
        } else {
            $this->from->setTimestamp($logRepository->getFirstTimestampByFilter($filter));
        }

        if ($filter->getTill() !== null) {
            $this->till->setTimestamp($filter->getTill());
        } elseif ($logRepository->getFirstTimestampByFilter($filter, true) > 0) {
            $this->till->setTimestamp($logRepository->getFirstTimestampByFilter($filter, true));
        }

        $this->traffic = $logRepository->getTrafficSumByFilter($filter);
    }

    public function getTraffic(): float
    {
        return $this->traffic;
    }

    public function getFrom(): \DateTime
    {
        return $this->from;
    }

    public function getTill(): \DateTime
    {
        return $this->till;
    }
}
