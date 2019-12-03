<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\EventListener;

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

use Bitmotion\SecureDownloads\Service\SecureDownloadService;
use TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

/**
 * This event listener listens to PSR-14 events given in TYPO3 10 and above.
 */
class SecureDownloadsEventListener implements SingletonInterface
{
    /**
     * @var SecureDownloadService
     */
    protected $sdlService;

    /**
     * @var EnvironmentService
     */
    protected $environmentService;

    public function __construct()
    {
        $this->sdlService = GeneralUtility::makeInstance(SecureDownloadService::class);
        $this->environmentService = GeneralUtility::makeInstance(EnvironmentService::class);
    }

    /**
     * This will secure a link when given file is underneath a protected directory and the file type matches the configured
     * file types. It will blank the URL of files when we are in backend context so that no thumbnails will be shown.
     *
     * @param GeneratePublicUrlForResourceEvent $event The event.
     */
    public function onResourceStorageEmitPreGeneratePublicUrlSignal(GeneratePublicUrlForResourceEvent $event): void
    {
        $driver = $event->getDriver();
        $resource = $event->getResource();

        if ($driver instanceof LocalDriver && ($resource instanceof File || $resource instanceof ProcessedFile)) {
            try {
                $publicUrl = $driver->getPublicUrl($resource->getIdentifier());
                if ($this->sdlService->pathShouldBeSecured($publicUrl)) {
                    if ($this->environmentService->isEnvironmentInFrontendMode()) {
                        $event->setPublicUrl($this->sdlService->publishResourceUri($publicUrl));
                    } elseif ($this->environmentService->isEnvironmentInBackendMode()) {
                        $event->setPublicUrl('');
                    }
                }
            } catch (Exception $exception) {
                // Do nothing.
            }
        }
    }

    /**
     * Will ad an overlay icon to secured directories and files when browsing the file list module.
     *
     * @param ModifyIconForResourcePropertiesEvent $event The event.
     */
    public function onIconFactoryEmitBuildIconForResourceSignal(ModifyIconForResourcePropertiesEvent $event): void
    {
        $resource = $event->getResource();

        if ($resource instanceof Folder) {
            $publicUrl = $resource->getStorage()->getPublicUrl($resource);
            if ($this->sdlService->folderShouldBeSecured($publicUrl)) {
                $overlayIdentifier = 'overlay-restricted';
            }
        } elseif ($resource instanceof File && empty($resource->getPublicUrl())) {
            $overlayIdentifier = 'overlay-restricted';
        }

        $event->setOverlayIdentifier($overlayIdentifier ?? $event->getOverlayIdentifier());
    }
}
