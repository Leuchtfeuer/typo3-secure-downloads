<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\EventListener;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Leuchtfeuer\SecureDownloads\Resource\Driver\SecureDownloadsDriver;
use Leuchtfeuer\SecureDownloads\Service\SecureDownloadService;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent;
use TYPO3\CMS\Core\Resource\Driver\AbstractHierarchicalFilesystemDriver;
use TYPO3\CMS\Core\Resource\Event\GeneratePublicUrlForResourceEvent;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * This event listener listens to PSR-14 events given in TYPO3 10 and above.
 */
class SecureDownloadsEventListener implements SingletonInterface
{
    /**
     * @var SecureDownloadService
     */
    protected $secureDownloadService;

    public function __construct(SecureDownloadService $secureDownloadService)
    {
        $this->secureDownloadService = $secureDownloadService;
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

        if ($driver instanceof AbstractHierarchicalFilesystemDriver && ($resource instanceof File || $resource instanceof ProcessedFile)) {
            try {
                $publicUrl = $driver->getPublicUrl($resource->getIdentifier()) ?? '';
                if ($driver instanceof SecureDownloadsDriver || $this->secureDownloadService->pathShouldBeSecured($publicUrl)) {
                    $securedUrl = $this->getSecuredUrl($event->isRelativeToCurrentScript(), $publicUrl, $driver);
                    $event->setPublicUrl($securedUrl);
                }
            } catch (Exception $exception) {
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
                $publicUrl = $resource->getStorage()->getPublicUrl($resource) ?? $resource->getIdentifier();
                if ($this->secureDownloadService->folderShouldBeSecured($publicUrl)) {
                    $overlayIdentifier = 'overlay-restricted';
                }
            } elseif ($resource instanceof File) {
                $folder = $resource->getParentFolder();
                $publicUrl = ($folder->getStorage()->getPublicUrl($folder) ?? $folder->getIdentifier()) . $resource->getName();
                if ($this->secureDownloadService->pathShouldBeSecured($publicUrl)) {
                    $overlayIdentifier = 'overlay-restricted';
                }
            }
        }

        $event->setOverlayIdentifier($overlayIdentifier ?? $event->getOverlayIdentifier());
    }

    /**
     * Returns the encrypted URL.
     *
     * @param bool                                 $relativeToCurrentScript Whether the $publicUrl is relative to current script
     *                                                                      or not.
     * @param string                               $publicUrl               The public URL to the file.
     * @param AbstractHierarchicalFilesystemDriver $driver                  The driver which is responsible for the file.
     *
     * @return string The secured URL
     */
    protected function getSecuredUrl(bool $relativeToCurrentScript, string $publicUrl, AbstractHierarchicalFilesystemDriver $driver): string
    {
        if ($relativeToCurrentScript === true) {
            $absolutePathToContainingFolder = PathUtility::dirname(
                sprintf(
                    '%s/%s',
                    Environment::getPublicPath(),
                    $driver->getDefaultFolder()
                )
            );

            $pathPart = PathUtility::getRelativePathTo($absolutePathToContainingFolder);
        }

        return ($pathPart ?? '') . $this->secureDownloadService->getResourceUrl($publicUrl);
    }
}
