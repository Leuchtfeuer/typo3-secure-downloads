<?php
namespace Bitmotion\SecureDownloads\Configuration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Bitmotion GmbH (typo3-ext@bitmotion.de)
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\SingletonInterface;

class ConfigurationManager implements SingletonInterface
{
    /**
     * @var string
     */
    protected $extensionKey = 'secure_downloads';

    /**
     * @var array
     */
    protected $configuration = [];

    /**
     * @param string|null $extensionKey
     */
    public function __construct($extensionKey = null)
    {
        $this->extensionKey = $extensionKey ?: $this->extensionKey;
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extensionKey])) {
            $this->configuration = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$this->extensionKey]);
        }
    }

    /**
     * @param string $key
     *
     * @return mixed
     */
    public function getValue($key)
    {
        if (is_array($this->configuration) && array_key_exists($key, $this->configuration)) {

            return $this->configuration[$key];
        }

        return null;
    }
}
