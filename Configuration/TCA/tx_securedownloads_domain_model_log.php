<?php
return array(
	'ctrl' => array(
		'title'	=> 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log',
		'label' => 'file_id',
		'tstamp' => 'tstamp',
		'crdate' => 'crdate',
		'cruser_id' => 'cruser_id',
		'dividers2tabs' => TRUE,
		'versioningWS' => 2,
		'versioning_followPages' => TRUE,

		'languageField' => 'sys_language_uid',
		'transOrigPointerField' => 'l10n_parent',
		'transOrigDiffSourceField' => 'l10n_diffsource',
		'delete' => 'deleted',
		'enablecolumns' => array(
			'disabled' => 'hidden',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
		),
		'hideTable' => TRUE,
		'searchFields' => 'file_id,file_name,file_path,file_size,file_type,media_type,bytes_downloaded,protected,host,typo3_mode,user,page,tstamp,',
		'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('secure_downloads') . 'Resources/Public/Icons/tx_securedownloads_domain_model_log.png'
	),
	'interface' => array(
		'showRecordFieldList' => 'sys_language_uid, l10n_parent, l10n_diffsource, hidden, file_id, file_name, file_path, file_size, file_type, media_type, bytes_downloaded, protected, host, typo3_mode, user, page, tstamp',
	),
	'types' => array(
		'1' => array('showitem' => 'sys_language_uid;;;;1-1-1, l10n_parent, l10n_diffsource, hidden;;1, file_id, file_name, file_path, file_size, file_type, media_type, bytes_downloaded, protected, host, typo3_mode, user, page, tstamp, --div--;LLL:EXT:cms/locallang_ttc.xlf:tabs.access, starttime, endtime'),
	),
	'palettes' => array(
		'1' => array('showitem' => ''),
	),
	'columns' => array(
	
		'sys_language_uid' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.language',
			'config' => array(
				'type' => 'select',
				'renderType' => 'selectSingle',
				'foreign_table' => 'sys_language',
				'foreign_table_where' => 'ORDER BY sys_language.title',
				'items' => array(
					array('LLL:EXT:lang/locallang_general.xlf:LGL.allLanguages', -1),
					array('LLL:EXT:lang/locallang_general.xlf:LGL.default_value', 0)
				),
			),
		),
		'l10n_parent' => array(
			'displayCond' => 'FIELD:sys_language_uid:>:0',
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.l18n_parent',
			'config' => array(
				'type' => 'select',
				'renderType' => 'selectSingle',
				'items' => array(
					array('', 0),
				),
				'foreign_table' => 'tx_securedownloads_domain_model_log',
				'foreign_table_where' => 'AND tx_securedownloads_domain_model_log.pid=###CURRENT_PID### AND tx_securedownloads_domain_model_log.sys_language_uid IN (-1,0)',
			),
		),
		'l10n_diffsource' => array(
			'config' => array(
				'type' => 'passthrough',
			),
		),

		't3ver_label' => array(
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.versionLabel',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'max' => 255,
			)
		),
	
		'hidden' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.hidden',
			'config' => array(
				'type' => 'check',
			),
		),
		'starttime' => array(
			'exclude' => 0,
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.starttime',
			'config' => array(
				'type' => 'input',
				'size' => 13,
				'max' => 20,
				'eval' => 'datetime',
				'checkbox' => 0,
				'default' => 0,
				'range' => array(
					'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y'))
				),
			),
		),
		'endtime' => array(
			'exclude' => 0,
			'l10n_mode' => 'mergeIfNotBlank',
			'label' => 'LLL:EXT:lang/locallang_general.xlf:LGL.endtime',
			'config' => array(
				'type' => 'input',
				'size' => 13,
				'max' => 20,
				'eval' => 'datetime',
				'checkbox' => 0,
				'default' => 0,
				'range' => array(
					'lower' => mktime(0, 0, 0, date('m'), date('d'), date('Y'))
				),
			),
		),

		'file_id' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.file_id',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'file_name' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.file_name',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'file_path' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.file_path',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'file_size' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.file_size',
			'config' => array(
				'type' => 'input',
				'size' => 4,
				'eval' => 'int'
			)
		),
		'file_type' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.file_type',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'media_type' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.media_type',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'bytes_downloaded' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.bytes_downloaded',
			'config' => array(
				'type' => 'input',
				'size' => 4,
				'eval' => 'int'
			)
		),
		'protected' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.protected',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'host' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.host',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'typo3_mode' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.typo3_mode',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'user' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.user',
			'config' => array(
				'type' => 'input',
				'size' => 30,
				'eval' => 'trim'
			),
		),
		'page' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.page',
			'config' => array(
				'type' => 'input',
				'size' => 4,
				'eval' => 'int'
			)
		),
		'tstamp' => array(
			'exclude' => 0,
			'label' => 'LLL:EXT:secure_downloads/Resources/Private/Language/locallang_db.xlf:tx_securedownloads_domain_model_log.tstamp',
			'config' => array(
				'type' => 'input',
				'size' => 4,
				'eval' => 'int'
			)
		),
		
	),
);