<?php
declare(strict_types = 1);

return [
    'ctrl' => [
        'title' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log',
        'label' => 'file_id',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'versioningWS' => false,
        'enablecolumns' => [],
        'hideTable' => true,
        'searchFields' => '',
        'iconfile' => 'EXT:secure_downloads/Resources/Public/Icons/tx_securedownloads_domain_model_log.png',
    ],
    'interface' => [
        'showRecordFieldList' => '',
    ],
    'types' => [
        '1' => ['showitem' => ''],
    ],
    'palettes' => [
        '1' => ['showitem' => ''],
    ],
    'columns' => [
        'file_id' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.file_id',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'file_name' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.file_name',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'file_path' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.file_path',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'file_size' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.file_size',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int',
            ],
        ],
        'file_type' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.file_type',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'media_type' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.media_type',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'protected' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.protected',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'host' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.host',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'user' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.user',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim',
            ],
        ],
        'page' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.page',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int',
            ],
        ],
        'tstamp' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.tstamp',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int',
            ],
        ],
    ],
];
