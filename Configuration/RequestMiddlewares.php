<?php
declare(strict_types=1);

return [
    'frontend' => [
        'bitmotion/secure-downloads/file-delivery' => [
            'target' => \Bitmotion\SecureDownloads\Middleware\FileDeliveryMiddleware::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
            'before' => [
                'typo3/cms-frontend/site',
            ],
        ],
    ],
];
