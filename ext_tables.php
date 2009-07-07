<?php
if (!defined ('TYPO3_MODE'))     die ('Access denied.');

$_EXTCONF = unserialize($_EXTCONF);

if (TYPO3_MODE == 'BE' AND $_EXTCONF['log'])	{
	t3lib_extMgm::addModule('tools','txnawsecuredlM1','',t3lib_extMgm::extPath($_EXTKEY).'modLog/');
}

unset ($_EXTCONF);

?>