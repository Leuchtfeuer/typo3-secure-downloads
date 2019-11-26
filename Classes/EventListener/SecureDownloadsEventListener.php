<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\EventListener;

use Bitmotion\SecureDownloads\Resource\Publishing\ResourcePublisher;
use Bitmotion\SecureDownloads\Service\SecureDownloadService;
use TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

class SecureDownloadsEventListener implements SingletonInterface
{
    protected $sdlService;

    protected $environmentService;

    public function __construct()
    {
        $resourcePublisher = GeneralUtility::makeInstance(ObjectManager::class)->get(ResourcePublisher::class);
        $this->sdlService = GeneralUtility::makeInstance(SecureDownloadService::class, $resourcePublisher);
        $this->environmentService = GeneralUtility::makeInstance(EnvironmentService::class);
    }

    public function onResourceStorageEmitPreGeneratePublicUrlSignal(GeneratePublicUrlForResourceEvent $event): void
    {
        $driver = $event->getDriver();
        $resource = $event->getResource();

        if ($driver instanceof LocalDriver && $resource instanceof File) {
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
