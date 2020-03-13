<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

class HookUtility
{
    const VENDOR_NAME = 'bitmotion';

    const EXTENSION_NAME = 'secure_downloads';

    /**
     * Executes hooks.
     *
     * @param string $category The category of the hook.
     * @param string $name     The name of the hook within the category namespace.
     * @param mixed  $params   Various parameters handled by the hook.
     * @param object $ref      Reference to parent object.
     */
    public static function executeHook(string $category, string $name, &$params, object &$ref): void
    {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][self::VENDOR_NAME][self::EXTENSION_NAME][$category][$name] ?? [] as $_funcRef) {
            if ($_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $params, $ref);
            }
        }
    }
}
