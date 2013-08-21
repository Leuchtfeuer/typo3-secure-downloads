<?php
if (!defined ("TYPO3_MODE")) {
	die ("Access denied.");
}

$TYPO3_CONF_VARS['FE']['eID_include']['tx_nawsecuredl'] = 'EXT:naw_securedl/class.tx_nawsecuredl_output.php';
$TYPO3_CONF_VARS['SC_OPTIONS']['tslib/class.tslib_fe.php']['contentPostProc-output'][] = ':&Tx_NawSecuredl_Service_SecureDownloadService->parseFE';

# TYPO3 > 6.0 (will be ignored in lower versions)
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']['TYPO3\\CMS\\Core\\Resource\\Driver\\LocalDriver'] = array(
	'className' => 'Bitmotion\\NawSecuredl\\Resource\\Driver\\LocalDriver',
);

# TYPO3 < 6.0 (will be ignored in higher versions)
$TYPO3_CONF_VARS['BE']['XCLASS']['typo3/class.file_list.inc'] = t3lib_extMgm::extPath($_EXTKEY) . 'Classes/Xclass/class.ux_fileList.inc';
$TYPO3_CONF_VARS['FE']['XCLASS']['tslib/showpic.php'] = t3lib_extMgm::extPath($_EXTKEY) . 'Classes/Xclass/class.ux_SC_tslib_showpic.php';
?>