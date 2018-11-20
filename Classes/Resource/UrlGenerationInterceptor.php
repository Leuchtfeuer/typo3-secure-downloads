<?php

namespace Bitmotion\SecureDownloads\Resource;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Bitmotion GmbH (typo3-ext@bitmotion.de)
 *
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

use Bitmotion\SecureDownloads\Resource\Publishing\ResourcePublisher;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class UrlGenerationInterceptor
 * @package Bitmotion\SecureDownloads\Resource
 */
class UrlGenerationInterceptor
{
    /**
     * @var ResourcePublisher
     */
    protected $resourcePublisher;

    /**
     * @param ResourcePublisher $resourcePublisher
     */
    public function injectResourcePublisher(ResourcePublisher $resourcePublisher)
    {
        $this->resourcePublisher = $resourcePublisher;
    }

    /**
     * @param ResourceStorage $storage
     * @param AbstractDriver $driver
     * @param ResourceInterface $resource
     * @param boolean $relativeToCurrentScript
     * @param array $urlData
     */
    public function getPublicUrl(
        ResourceStorage $storage,
        AbstractDriver $driver,
        ResourceInterface $resource,
        bool $relativeToCurrentScript,
        array $urlData
    ) {
        if (!$driver instanceof LocalDriver) {
            // We cannot handle other files than local files yet
            return;
        }
        $publicUrl = $this->resourcePublisher->getResourceWebUri($resource);
        if ($publicUrl !== false) {
            // If requested, make the path relative to the current script in order to make it possible
            // to use the relative file
            if ($relativeToCurrentScript) {
                $publicUrl = PathUtility::getRelativePathTo(PathUtility::dirname((PATH_site . $publicUrl))) . PathUtility::basename($publicUrl);
            }
            // $urlData['publicUrl'] is passed by reference, so we can change that here and the value will be taken into account
            $urlData['publicUrl'] = $publicUrl;
        }
    }
}
