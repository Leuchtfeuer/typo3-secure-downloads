<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "naw_securedl".
 *
 * Auto generated 20-08-2013 16:44
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Secure Downloads',
	'description' => '"Secure Download": Apply TYPO3 access rights to ALL file assets (PDFs, TGZs or JPGs etc. - configurable) - protect them from direct access.',
	'category' => 'fe',
	'shy' => 0,
	'version' => '1.7.2',
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'modLog',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Dietrich Heise, Helmut Hummel',
	'author_email' => 'typo3-ext(at)bitmotion.de',
	'author_company' => '<a href="http://www.bitmotion.de" target="_blank">bitmotion.de</a>',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'php' => '5.3.0-5.5.99',
			'typo3' => '4.5.0-6.1.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:27:{s:9:"ChangeLog";s:4:"6f09";s:28:"class.tx_nawsecuredl_log.php";s:4:"25c5";s:31:"class.tx_nawsecuredl_output.php";s:4:"4f10";s:21:"class.ux_fileList.inc";s:4:"05e0";s:29:"class.ux_SC_tslib_showpic.php";s:4:"81d0";s:16:"ext_autoload.php";s:4:"2f51";s:21:"ext_conf_template.txt";s:4:"6714";s:12:"ext_icon.gif";s:4:"2dbb";s:17:"ext_localconf.php";s:4:"831e";s:14:"ext_tables.php";s:4:"9fc5";s:14:"ext_tables.sql";s:4:"ec93";s:8:"TODO.txt";s:4:"014d";s:37:"Classes/Driver/Xclass/LocalDriver.php";s:4:"f845";s:35:"Classes/Service/SecureDownloadService.php";s:4:"5bce";s:41:"Classes/Service/Tx_NawSecuredl_Service_SecureDownloadService.php";s:4:"dae3";s:14:"doc/manual.sxw";s:4:"c85e";s:37:"modLog/class.tx_nawsecuredl_table.php";s:4:"f26e";s:15:"modLog/conf.php";s:4:"9c2f";s:16:"modLog/index.php";s:4:"7cb0";s:20:"modLog/locallang.xml";s:4:"0b71";s:24:"modLog/locallang_mod.xml";s:4:"84d1";s:21:"modLog/moduleicon.gif";s:4:"691d";s:14:"res/_.htaccess";s:4:"e99d";s:20:"res/_.htaccess_allow";s:4:"5057";s:19:"res/_.htaccess_deny";s:4:"e99d";s:29:"tests/SecureDownloadServiceTest.php";s:4:"b077";s:35:"tests/tx_nawsecuredl_outputTest.php";s:4:"94bd";}',
	'suggests' => array(
	),
);

?>