<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Domain\Transfer;

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

use Bitmotion\SecureDownloads\Domain\Model\Log;
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

    public function __construct(QueryResultInterface $logEntries)
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
