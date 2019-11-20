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

/**
 * @deprecated Use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration instead.
 */
class ConfigurationManager implements SingletonInterface
{
    protected $extensionKey = 'secure_downloads';

    protected $configuration = [];

    /**
     * @deprecated
     */
    public function __construct(?string $extensionKey = null)
    {
        $this->extensionKey = $extensionKey ?: $this->extensionKey;
        // TODO: Deprecated since TYPO3 9.0
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extensionKey])) {
            $this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extensionKey]);
        }
    }

    /**
     * @deprecated
     */
    public function getValue(string $key): ?string
    {
        if (is_array($this->configuration) && isset($this->configuration[$key])) {
            return $this->configuration[$key];
        }

        return null;
    }
}
