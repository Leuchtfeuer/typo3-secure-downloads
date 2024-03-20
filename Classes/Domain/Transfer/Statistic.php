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

use Leuchtfeuer\SecureDownloads\Domain\Model\Log;
use Leuchtfeuer\SecureDownloads\Domain\Repository\LogRepository;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

class Statistic
{
    /**
     * @var float
     */
    protected $traffic = 0.00;

    /**
     * @var \DateTime
     */
    protected $from;

    /**
     * @var \DateTime
     */
    protected $till;

    public function __construct(Filter $filter, LogRepository $logRepository)
    {
        $this->from = new \DateTime();
        if ($filter->getFrom() !== null) {
            $this->from->setTimestamp($filter->getFrom());
        } else {
            $this->from->setTimestamp($logRepository->getFirstTimestampByFilter($filter));
        }

        $this->till = new \DateTime();
        if ($filter->getTill() !== null) {
            $this->till->setTimestamp($filter->getTill());
        } else {
            if ($logRepository->getFirstTimestampByFilter($filter, true) > 0) {
                $this->till->setTimestamp($logRepository->getFirstTimestampByFilter($filter, true));
            }
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
