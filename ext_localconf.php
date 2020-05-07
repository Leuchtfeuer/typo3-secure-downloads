<?php
defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function ($extensionKey) {
        // Load libraries when TYPO3 is not in composer mode
        if (\TYPO3\CMS\Core\Core\Environment::isComposerMode() === false) {
            require \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($extensionKey) . 'Libraries/vendor/autoload.php';
        }

        // Load extension configuration and add link prefix to additionalAbsRefPrefixDirectories
        $configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration::class);
        $GLOBALS['TYPO3_CONF_VARS']['FE']['additionalAbsRefPrefixDirectories'] .= sprintf(',%s', $configuration->getLinkPrefix());

        // Register default checks
        if (!isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey] = [
                'checks' => [],
                'tokenClass' => \Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\DefaultToken::class,
            ];
        }

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['checks']['user'] = \Leuchtfeuer\SecureDownloads\Security\UserCheck::class;
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF'][$extensionKey]['checks']['userGroup'] = \Leuchtfeuer\SecureDownloads\Security\UserGroupCheck::class;

        // Add MimeTypes
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'] += \Leuchtfeuer\SecureDownloads\MimeTypes::ADDITIONAL_MIME_TYPES;

    }, 'secure_downloads'
);


