<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Resource\Driver;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use TYPO3\CMS\Core\Resource\Driver\LocalDriver;

class SecureDownloadsDriver extends LocalDriver
{
    const DRIVER_SHORT_NAME = 'sdl';

    const DRIVER_NAME = 'Secure Downloads';

    const BASE_PATH = 'sdl/';
}
