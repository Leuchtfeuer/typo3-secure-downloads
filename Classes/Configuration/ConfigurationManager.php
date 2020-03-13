<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Configuration;

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
        trigger_error('Class ConfigurationManager will be removed in version 5. Use ExtensionConfiguration instead.', E_USER_DEPRECATED);

        $this->extensionKey = $extensionKey ?: $this->extensionKey;
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extensionKey])) {
            $this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extensionKey]);
        }
    }

    /**
     * @deprecated
     */
    public function getValue(string $key): ?string
    {
        trigger_error('Method getValue() will be removed in version 5.', E_USER_DEPRECATED);

        if (is_array($this->configuration) && isset($this->configuration[$key])) {
            return $this->configuration[$key];
        }

        return null;
    }
}
