<?php
declare(strict_types = 1);

if (version_compare(TYPO3_version, '10.0.0', '>=')) {
    $before = [
        'typo3/cms-frontend/base-redirect-resolver',
    ];

    if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('redirects')) {
        $before[] = 'typo3/cms-redirects/redirecthandler';
    }
} else {
    $before = [
        'typo3/cms-frontend/site',
    ];
}

return [
    'frontend' => [
        'bitmotion/secure-downloads/file-delivery' => [
            'target' => \Bitmotion\SecureDownloads\Middleware\FileDeliveryMiddleware::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
            'before' => $before,
        ],
    ],
];
