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
use Bitmotion\SecureDownloads\Service\SecureDownloadService;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UrlGenerationInterceptor implements SingletonInterface
{
    protected $sdlService;

    public function __construct(ResourcePublisher $resourcePublisher)
    {
        $this->sdlService = GeneralUtility::makeInstance(SecureDownloadService::class, $resourcePublisher);
    }

    public function getPublicUrl(ResourceStorage $storage, AbstractDriver $driver, ResourceInterface $resourceObject, bool $relativeToCurrentScript, array $urlData): void
    {
        if ($driver instanceof LocalDriver) {
            try {
                $publicUrl = $driver->getPublicUrl($resourceObject->getIdentifier());
                if ($this->sdlService->pathShouldBeSecured($publicUrl)) {
                    $urlData['publicUrl'] = $this->sdlService->publishResourceUri($publicUrl);
                }
            } catch (Exception $exception) {
                // Do nothing.
            }
        }
    }
}
