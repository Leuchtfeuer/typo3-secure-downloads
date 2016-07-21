<?php

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}

$_EXTCONF = unserialize($_EXTCONF);

/**
 * Registers a Backend Module
 */
if (TYPO3_MODE == 'BE' && $_EXTCONF['log']) {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule('Bitmotion.' . $_EXTKEY, 'web',
        // Make module a submodule of 'web'
        'web',    // Submodule key
        '10',    // Position
        [
            'Log' => 'list',
        ], [
            'access' => 'user,group',
            'icon' => 'EXT:' . $_EXTKEY . '/ext_icon.svg',
            'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_log.xlf',
        ]);
}

unset ($_EXTCONF);