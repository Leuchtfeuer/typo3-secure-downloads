<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Domain\Model;

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

use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * @deprecated Will be removed with Version 5. Use Statistic DTO instead.
 */
class Statistic extends \Bitmotion\SecureDownloads\Domain\Transfer\Statistic
{
    public function __construct(QueryResultInterface $logEntries)
    {
        trigger_error('Class Statistic is deprecated. Use Statistic DTO instead.', E_USER_DEPRECATED);

        parent::__construct($logEntries);
    }
}
