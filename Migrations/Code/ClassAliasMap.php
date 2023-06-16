<?php

return [
    '\\' . \Bitmotion\SecureDownloads\Cache\AbstractCache::class => \Leuchtfeuer\SecureDownloads\Cache\AbstractCache::class,
    '\\' . \Bitmotion\SecureDownloads\Cache\DecodeCache::class => \Leuchtfeuer\SecureDownloads\Cache\DecodeCache::class,
    '\\' . \Bitmotion\SecureDownloads\Cache\EncodeCache::class => \Leuchtfeuer\SecureDownloads\Cache\EncodeCache::class,
    '\\' . \Bitmotion\SecureDownloads\Controller\LogController::class => \Leuchtfeuer\SecureDownloads\Controller\LogController::class,
    '\\' . \Bitmotion\SecureDownloads\Domain\Model\Log::class => \Leuchtfeuer\SecureDownloads\Domain\Model\Log::class,
    '\\' . \Bitmotion\SecureDownloads\Domain\Repository\LogRepository::class => \Leuchtfeuer\SecureDownloads\Domain\Repository\LogRepository::class,
    '\\' . \Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration::class => \Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration::class,
    '\\' . \Bitmotion\SecureDownloads\Domain\Transfer\Filter::class => \Leuchtfeuer\SecureDownloads\Domain\Transfer\Filter::class,
    '\\' . \Bitmotion\SecureDownloads\Domain\Transfer\Statistic::class => \Leuchtfeuer\SecureDownloads\Domain\Transfer\Statistic::class,
    '\\' . \Bitmotion\SecureDownloads\EventListener\SecureDownloadsEventListener::class => \Leuchtfeuer\SecureDownloads\EventListener\SecureDownloadsEventListener::class,
    '\\' . \Bitmotion\SecureDownloads\Factory\SecureLinkFactory::class => \Leuchtfeuer\SecureDownloads\Factory\SecureLinkFactory::class,
    '\\' . \Bitmotion\SecureDownloads\Middleware\FileDeliveryMiddleware::class => \Leuchtfeuer\SecureDownloads\Middleware\FileDeliveryMiddleware::class,
    '\\' . \Bitmotion\SecureDownloads\Resource\FileDelivery::class => \Leuchtfeuer\SecureDownloads\Resource\FileDelivery::class,
    '\Bitmotion\SecureDownloads\Resource\Event\AfterFileRetrievedEvent' => \Leuchtfeuer\SecureDownloads\Resource\Event\AfterFileRetrievedEvent::class,
    '\Bitmotion\SecureDownloads\Resource\Event\BeforeReadDeliverEvent' => \Leuchtfeuer\SecureDownloads\Resource\Event\BeforeReadDeliverEvent::class,
    '\Bitmotion\SecureDownloads\Resource\Event\OutputInitializationEvent' => \Leuchtfeuer\SecureDownloads\Resource\Event\OutputInitializationEvent::class,
    '\\' . \Bitmotion\SecureDownloads\Service\SecureDownloadService::class => \Leuchtfeuer\SecureDownloads\Service\SecureDownloadService::class,
    '\\' . \Bitmotion\SecureDownloads\UserFunctions\CheckConfiguration::class => \Leuchtfeuer\SecureDownloads\UserFunctions\CheckConfiguration::class,
];
