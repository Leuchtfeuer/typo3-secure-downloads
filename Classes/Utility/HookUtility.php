<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

class HookUtility
{
    public static function executeHook(string $category, string $name, &$params, object &$ref)
    {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['bitmotion']['secure_downloads'][$category][$name] ?? [] as $_funcRef) {
            if ($_funcRef) {
                GeneralUtility::callUserFunction($_funcRef, $params, $ref);
            }
        }
    }
}
