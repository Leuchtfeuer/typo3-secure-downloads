<?php

declare(strict_types=1);

use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

defined('TYPO3') || die();

if (isset($GLOBALS['TCA']['tx_scheduler_task'])) {
    // @extensionScannerIgnoreLine
    $GLOBALS['TCA']['tx_scheduler_task']['types'][TableGarbageCollectionTask::class]['taskOptions']['tables']['tx_securedownloads_domain_model_log'] = [
        'dateField' => 'tstamp',
        'expirePeriod' => 180,
    ];
}