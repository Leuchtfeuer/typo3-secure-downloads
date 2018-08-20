<?php
namespace Bitmotion\SecureDownloads\Security\Authorization\Resource;

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

/**
 * Interface AccessRestrictionPublisherInterface
 * @package Bitmotion\SecureDownloads\Security\Authorization\Resource
 */
interface AccessRestrictionPublisherInterface
{

    /**
     * Publishes access restrictions for file path.
     * This could be a e.g. .htaccess file to deny public access for the directory or its files
     *
     * @param string $path The path to publish the restrictions for
     *
     * @return void
     */
    public function publishAccessRestrictionsForPath(string $path);
}
