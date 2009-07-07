<?php

########################################################################
# Extension Manager/Repository config file for ext: "naw_securedl"
#
# Auto generated 30-01-2009 16:29
#
# Manual updates:
# Only the data in the array - anything else is removed by next write.
# "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Secure Downloads',
	'description' => '"Secure Download": Apply TYPO3 access rights to ALL file assets (PDFs, TGZs or JPGs etc. - configurable) - protect them from direct access.',
	'category' => 'fe',
	'shy' => 0,
	'version' => '1.0.0',
	'dependencies' => '',
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
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:14:{s:9:"ChangeLog";s:4:"120e";s:8:"TODO.txt";s:4:"014d";s:24:"class.tx_nawsecuredl.php";s:4:"7b2b";s:31:"class.tx_nawsecuredl_output.php";s:4:"9c8a";s:29:"class.ux_SC_tslib_showpic.php";s:4:"6519";s:28:"class.ux_class.file_list.inc";s:4:"7ebe";s:21:"ext_conf_template.txt";s:4:"98a7";s:12:"ext_icon.gif";s:4:"2dbb";s:17:"ext_localconf.php";s:4:"37d6";s:14:"ext_tables.sql";s:4:"9554";s:14:"res/_.htaccess";s:4:"f453";s:20:"res/_.htaccess_allow";s:4:"01b3";s:19:"res/_.htaccess_deny";s:4:"f453";s:14:"doc/manual.sxw";s:4:"7bea";}',
	'suggests' => array(
	),
);

?>
