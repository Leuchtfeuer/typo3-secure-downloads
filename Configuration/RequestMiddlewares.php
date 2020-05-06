<?php
declare(strict_types = 1);

$before = [
    'typo3/cms-frontend/base-redirect-resolver',
];

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('redirects')) {
    $before[] = 'typo3/cms-redirects/redirecthandler';
}

return [
    'frontend' => [
        'leuchtfeuer/secure-downloads/file-delivery' => [
            'target' => \Leuchtfeuer\SecureDownloads\Middleware\FileDeliveryMiddleware::class,
            'after' => [
                'typo3/cms-frontend/authentication',
            ],
            'before' => $before,
        ],
    ],
];
