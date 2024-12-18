<?php

declare(strict_types=1);

/*
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\SecureDownloads\EventListener;

use Leuchtfeuer\SecureDownloads\Resource\Driver\SecureDownloadsDriver;
use Leuchtfeuer\SecureDownloads\Service\SecureDownloadService;
use TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent;
use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * This event listener listens to PSR-14 events given in TYPO3 10 and above.
 */
class SecureDownloadsEventListener implements SingletonInterface
{
    public function __construct(protected SecureDownloadService $secureDownloadService) {}

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

        if ($driver instanceof AbstractHierarchicalFilesystemDriver && ($resource instanceof File || $resource instanceof ProcessedFile) && ($resource->getStorage()->isPublic() || $driver instanceof SecureDownloadsDriver)) {
            try {
                $originalPathShouldBeSecured = false;
                if ($driver instanceof SecureDownloadsDriver) {
                    $driver->determineSecureDownloadsDriverBaseUrl();
                }
                if ($resource instanceof ProcessedFile) {
                    // @extensionScannerIgnoreLine
                    $originalPublicUrl = $driver->getPublicUrl($resource->getOriginalFile()->getIdentifier()) ?? '';
                    $originalPathShouldBeSecured = $this->secureDownloadService->pathShouldBeSecured($originalPublicUrl);
                }
                // @extensionScannerIgnoreLine
                $publicUrl = $driver->getPublicUrl($resource->getIdentifier()) ?? '';
                if ($originalPathShouldBeSecured || $driver instanceof SecureDownloadsDriver || $this->secureDownloadService->pathShouldBeSecured($publicUrl)) {
                    $securedUrl = $this->secureDownloadService->getResourceUrl($publicUrl);
                    $event->setPublicUrl($securedUrl);
                }
                if ($driver instanceof SecureDownloadsDriver) {
                    $event->setPublicUrl('/' . $event->getPublicUrl());
                }
            } catch (Exception) {
                // Do nothing.
            }
        }
    }

    /**
     * Will add an overlay icon to secured directories and files when browsing the file list module.
     *
     * @param ModifyIconForResourcePropertiesEvent $event The event.
     */
    public function onIconFactoryEmitBuildIconForResourceSignal(ModifyIconForResourcePropertiesEvent $event): void
    {
        $resource = $event->getResource();
        $driverType = $resource->getStorage()->getDriverType();

        if ($driverType === SecureDownloadsDriver::DRIVER_SHORT_NAME) {
            $overlayIdentifier = 'overlay-restricted';
        } else {
            if ($resource instanceof Folder) {
                // @extensionScannerIgnoreLine
                $publicUrl = $resource->getStorage()->getPublicUrl($resource) ?? $resource->getIdentifier();
                if ($this->secureDownloadService->folderShouldBeSecured($publicUrl)) {
                    $overlayIdentifier = 'overlay-restricted';
                }
            } elseif ($resource instanceof File) {
                try {
                    $folder = $resource->getParentFolder();
                    // @extensionScannerIgnoreLine
                    $publicUrl = ($folder->getStorage()->getPublicUrl($folder) ?? $folder->getIdentifier()) . $resource->getName();
                    if ($this->secureDownloadService->pathShouldBeSecured($publicUrl)) {
                        $overlayIdentifier = 'overlay-restricted';
                    }
                } catch (InsufficientFolderAccessPermissionsException) {
                    return;
                }
            }
        }

        $event->setOverlayIdentifier($overlayIdentifier ?? $event->getOverlayIdentifier());
    }
}
