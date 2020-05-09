<?php

return [
    '\Bitmotion\SecureDownloads\Cache\AbstractCache' => \Leuchtfeuer\SecureDownloads\Cache\AbstractCache::class,
    '\Bitmotion\SecureDownloads\Cache\DecodeCache' => \Leuchtfeuer\SecureDownloads\Cache\DecodeCache::class,
    '\Bitmotion\SecureDownloads\Cache\EncodeCache' => \Leuchtfeuer\SecureDownloads\Cache\EncodeCache::class,
    '\Bitmotion\SecureDownloads\Controller\LogController' => \Leuchtfeuer\SecureDownloads\Controller\LogController::class,
    '\Bitmotion\SecureDownloads\Domain\Model\Log' => \Leuchtfeuer\SecureDownloads\Domain\Model\Log::class,
    '\Bitmotion\SecureDownloads\Domain\Repository\LogRepository' => \Leuchtfeuer\SecureDownloads\Domain\Repository\LogRepository::class,
    '\Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration' => \Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration::class,
    '\Bitmotion\SecureDownloads\Domain\Transfer\Filter' => \Leuchtfeuer\SecureDownloads\Domain\Transfer\Filter::class,
    '\Bitmotion\SecureDownloads\Domain\Transfer\Statistic' => \Leuchtfeuer\SecureDownloads\Domain\Transfer\Statistic::class,
    '\Bitmotion\SecureDownloads\EventListener\SecureDownloadsEventListener' => \Leuchtfeuer\SecureDownloads\EventListener\SecureDownloadsEventListener::class,
    '\Bitmotion\SecureDownloads\Factory\SecureLinkFactory' => \Leuchtfeuer\SecureDownloads\Factory\SecureLinkFactory::class,
    '\Bitmotion\SecureDownloads\Middleware\FileDeliveryMiddleware' => \Leuchtfeuer\SecureDownloads\Middleware\FileDeliveryMiddleware::class,
    '\Bitmotion\SecureDownloads\Resource\FileDelivery' => \Leuchtfeuer\SecureDownloads\Resource\FileDelivery::class,
    '\Bitmotion\SecureDownloads\Resource\Event\AfterFileRetrievedEvent' => \Leuchtfeuer\SecureDownloads\Resource\Event\AfterFileRetrievedEvent::class,
    '\Bitmotion\SecureDownloads\Resource\Event\BeforeReadDeliverEvent' => \Leuchtfeuer\SecureDownloads\Resource\Event\BeforeReadDeliverEvent::class,
    '\Bitmotion\SecureDownloads\Resource\Event\OutputInitializationEvent' => \Leuchtfeuer\SecureDownloads\Resource\Event\OutputInitializationEvent::class,
    '\Bitmotion\SecureDownloads\Service\SecureDownloadService' => \Leuchtfeuer\SecureDownloads\Service\SecureDownloadService::class,
    '\Bitmotion\SecureDownloads\UserFunctions\CheckConfiguration' => \Leuchtfeuer\SecureDownloads\UserFunctions\CheckConfiguration::class,
];
