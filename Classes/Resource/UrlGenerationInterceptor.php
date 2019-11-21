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

use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Bitmotion\SecureDownloads\Resource\Publishing\ResourcePublisher;
use Bitmotion\SecureDownloads\Service\SecureDownloadService;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\CMS\Core\Resource\Driver\LocalDriver;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;

class UrlGenerationInterceptor implements SingletonInterface
{
    protected $sdlService;

    protected $extensionConfiguration;

    protected $securedFileTypesPattern;

    public function __construct(ResourcePublisher $resourcePublisher)
    {
        $this->sdlService = GeneralUtility::makeInstance(SecureDownloadService::class, $resourcePublisher);
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $this->securedFileTypesPattern = sprintf('/^(%s)$/i', $this->extensionConfiguration->getSecuredFileTypes());
    }

    public function getPublicUrl(ResourceStorage $storage, AbstractDriver $driver, ResourceInterface $resourceObject, bool $relativeToCurrentScript, array $urlData): void
    {
        if ($driver instanceof LocalDriver) {
            try {
                $publicUrl = $driver->getPublicUrl($resourceObject->getIdentifier());

                if ($this->shouldBeSecured($publicUrl)) {
                    $urlData['publicUrl'] = $this->sdlService->publishResourceUri($publicUrl);
                }
            } catch (Exception $exception) {
                // Do nothing.
            }
        }
    }

    /**
     * Check whether file is located underneath a secured folder and file extension should matches file types pattern.
     */
    protected function shouldBeSecured(string $publicUrl): bool
    {
        foreach (explode('|', $this->extensionConfiguration->getSecuredDirs()) as $securedDir) {
            if (strpos($publicUrl, $securedDir) === 0) {
                $fileExtension = pathinfo($publicUrl, PATHINFO_EXTENSION);
                if (preg_match($this->securedFileTypesPattern, $fileExtension)) {
                    return true;
                }
            }
        }

        return false;
    }
}
