<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\Configuration;

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

use TYPO3\CMS\Core\SingletonInterface;

class ConfigurationManager implements SingletonInterface
{
    protected $extensionKey = 'secure_downloads';

    protected $configuration = [];

    /**
     * @param string|null $extensionKey
     */
    public function __construct($extensionKey = null)
    {
        $this->extensionKey = $extensionKey ?: $this->extensionKey;
        // TODO: Deprecated since TYPO3 9.0
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extensionKey])) {
            $this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extensionKey]);
        }
    }

    public function getValue(string $key)
    {
        if (is_array($this->configuration) && isset($this->configuration[$key])) {
            return $this->configuration[$key];
        }

        return null;
    }
}
