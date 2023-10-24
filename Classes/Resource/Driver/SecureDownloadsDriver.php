<?php

declare(strict_types=1);
namespace Leuchtfeuer\SecureDownloads\Resource\Driver;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * The Secure Downloads file storage.
 */
class SecureDownloadsDriver extends LocalDriver
{
    const DRIVER_SHORT_NAME = 'sdl';

    const DRIVER_NAME = 'Secure Downloads';

    const BASE_PATH = 'sdl/';

    public function determineSecureDownloadsDriverBaseUrl(): void
    {
        if ($this->baseUri === null) {
            if (!empty($this->configuration['baseUri'])) {
                $this->baseUri = rtrim($this->configuration['baseUri'], '/') . '/';
            } elseif (str_starts_with($this->absoluteBasePath, Environment::getPublicPath())) {
                // use site-relative URLs
                $temporaryBaseUri = rtrim(PathUtility::stripPathSitePrefix($this->absoluteBasePath), '/');
                if ($temporaryBaseUri !== '') {
                    $uriParts = explode('/', $temporaryBaseUri);
                    $uriParts = array_map('rawurlencode', $uriParts);
                    $temporaryBaseUri = implode('/', $uriParts) . '/';
                }
                $this->baseUri = $temporaryBaseUri;
            }
        }
    }
}
