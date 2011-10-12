<?php

########################################################################
# Extension Manager/Repository config file for ext "naw_securedl".
#
# Auto generated 12-10-2011 11:46
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Secure Downloads',
	'description' => '"Secure Download": Apply TYPO3 access rights to ALL file assets (PDFs, TGZs or JPGs etc. - configurable) - protect them from direct access.',
	'category' => 'fe',
	'shy' => 0,
	'version' => '1.5.1',
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
	'author_email' => 'typo3-ext(at)naw.info',
	'author_company' => '<a href="http://www.naw.info" target="_blank">naw.info</a>',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'php' => '5.2.0-5.3.99',
			'typo3' => '4.2.13-4.5.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:22:{s:9:"ChangeLog";s:4:"f217";s:8:"TODO.txt";s:4:"014d";s:24:"class.tx_nawsecuredl.php";s:4:"1b98";s:28:"class.tx_nawsecuredl_log.php";s:4:"3977";s:31:"class.tx_nawsecuredl_output.php";s:4:"e474";s:29:"class.ux_SC_tslib_showpic.php";s:4:"6519";s:21:"class.ux_fileList.inc";s:4:"1553";s:21:"ext_conf_template.txt";s:4:"c4e1";s:12:"ext_icon.gif";s:4:"2dbb";s:17:"ext_localconf.php";s:4:"7271";s:14:"ext_tables.php";s:4:"9fc5";s:14:"ext_tables.sql";s:4:"ec93";s:14:"doc/manual.sxw";s:4:"6b88";s:37:"modLog/class.tx_nawsecuredl_table.php";s:4:"361d";s:15:"modLog/conf.php";s:4:"9c2f";s:16:"modLog/index.php";s:4:"92fc";s:20:"modLog/locallang.xml";s:4:"85bc";s:24:"modLog/locallang_mod.xml";s:4:"3833";s:21:"modLog/moduleicon.gif";s:4:"691d";s:14:"res/_.htaccess";s:4:"e99d";s:20:"res/_.htaccess_allow";s:4:"5057";s:19:"res/_.htaccess_deny";s:4:"e99d";}',
	'suggests' => array(
	),
);

?>