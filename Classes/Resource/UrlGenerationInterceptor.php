<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\Resource;

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

use Bitmotion\SecureDownloads\Resource\Publishing\ResourcePublisher;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\PathUtility;

class UrlGenerationInterceptor
{
    protected $resourcePublisher;

    public function __construct(ResourcePublisher $resourcePublisher)
    {
        $this->resourcePublisher = $resourcePublisher;
    }

    public function getPublicUrl(
        ResourceStorage $storage,
        AbstractDriver $driver,
        ResourceInterface $resource,
        bool $relativeToCurrentScript,
        array $urlData
    ): void {
        if (!$driver instanceof LocalDriver) {
            // We cannot handle other files than local files yet
            return;
        }
        $publicUrl = $this->resourcePublisher->getResourceWebUri($resource);
        if ($publicUrl !== false) {
            // If requested, make the path relative to the current script in order to make it possible
            // to use the relative file
            if ($relativeToCurrentScript) {
                $publicUrl = PathUtility::getRelativePathTo(PathUtility::dirname((Environment::getPublicPath() . '/' . $publicUrl))) . PathUtility::basename($publicUrl);
            }
            // $urlData['publicUrl'] is passed by reference, so we can change that here and the value will be taken into account
            $urlData['publicUrl'] = $publicUrl;
        }
    }
}
