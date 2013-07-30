<?php
if (!defined ("TYPO3_MODE")) 	die ("Access denied.");

$TYPO3_CONF_VARS['FE']['eID_include']['tx_nawsecuredl'] = 'EXT:naw_securedl/class.tx_nawsecuredl_output.php';

$version = TYPO3_version;
$firstNumber = (int)$version{0};
# for TYPO3 > 6.0
if  ($firstNumber >= 6) {
	# for TYPO3 > 6.0
	$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver'] = array(
		'className' => 'Bm\\Securedl\Driver\\Xclass\\LocalDriver',
	);
	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'EXT:naw_securedl/Classes/Service/SecuredlService.php:&Bm\Securedl\Service\SecuredlService->parseFE';
}else{
	$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = 'EXT:naw_securedl/Classes/Service/Tx_NawSecuredlService.php:&Tx_NawSecuredlService->parseFE';
	$TYPO3_CONF_VARS['BE']['XCLASS']['typo3/class.file_list.inc'] = t3lib_extMgm::extPath($_EXTKEY)."class.ux_fileList.inc";
	$TYPO3_CONF_VARS['FE']['XCLASS']['tslib/showpic.php'] = t3lib_extMgm::extPath($_EXTKEY)."class.ux_SC_tslib_showpic.php";
}

?>