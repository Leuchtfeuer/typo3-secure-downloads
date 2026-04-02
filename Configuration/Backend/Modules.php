<?php

use Leuchtfeuer\SecureDownloads\Controller\LogController;

return [
    'web_sdl_traffic' => [
        'parent' => 'content',
        'position' => ['after' => 'content_status'],
        'access' => 'user',
        'path' => '/module/page/secure-downloads',
        'labels' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_log.xlf',
        'extensionName' => 'Secure Downloads',
        'iconIdentifier' => 'tx_securedownloads-module',
        'controllerActions' => [
            LogController::class => [
                'list',
                'clear'
            ],
        ],
    ],
];
