<?php
return [
    'ctrl' => [
        'title' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log',
        'label' => 'file_id',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'dividers2tabs' => true,
        'versioningWS' => false,
        'versioning_followPages' => false,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
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
        'sys_language_uid' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'sys_language',
                'foreign_table_where' => 'ORDER BY sys_language.title',
                'items' => [
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.allLanguages', -1],
                    ['LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.default_value', 0],
                ],
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'exclude' => 0,
            'label' => 'LLL:EXT:lang/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0],
                ],
                'foreign_table' => 'tx_securedownloads_domain_model_log',
                'foreign_table_where' => 'AND tx_securedownloads_domain_model_log.pid=###CURRENT_PID### AND tx_securedownloads_domain_model_log.sys_language_uid IN (-1,0)',
            ],
        ],
        'l10n_diffsource' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
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
        'bytes_downloaded' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.bytes_downloaded',
            'config' => [
                'type' => 'input',
                'size' => 4,
                'eval' => 'int',
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
        'typo3_mode' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.typo3_mode',
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