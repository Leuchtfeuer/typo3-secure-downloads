<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'EXT:naw_securedl/class.tx_nawsecuredl.php:&tx_nawsecuredl->parseFE';
$TYPO3_CONF_VARS['FE']['XCLASS']['tslib/showpic.php'] = t3lib_extMgm::extPath($_EXTKEY)."class.ux_SC_tslib_showpic.php";

$TYPO3_CONF_VARS['FE']['eID_include']['tx_nawsecuredl'] = 'EXT:naw_securedl/class.tx_nawsecuredl_output.php';

$TYPO3_CONF_VARS['BE']['XCLASS']['typo3/class.file_list.inc'] = t3lib_extMgm::extPath($_EXTKEY)."class.ux_fileList.inc";
?>