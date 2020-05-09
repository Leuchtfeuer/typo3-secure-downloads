<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Service;

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

use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\SecureDownloads\Factory\SecureLinkFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SecureDownloadService implements SingletonInterface
{
    protected $extensionConfiguration;

    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    /**
     * Check whether file is located underneath a secured folder and file extension should matches file types pattern.
     */
    public function pathShouldBeSecured(string $publicUrl): bool
    {
        if ($this->folderShouldBeSecured($publicUrl)) {
            if ($this->extensionConfiguration->getSecuredFileTypes() === ExtensionConfiguration::FILE_TYPES_WILDCARD) {
                return true;
            }

            $fileExtension = pathinfo($publicUrl, PATHINFO_EXTENSION);

            return (bool)preg_match($this->extensionConfiguration->getSecuredFileTypesPattern(), $fileExtension);
        }

        return false;
    }

    public function folderShouldBeSecured(string $publicUrl): bool
    {
        return (bool)preg_match($this->extensionConfiguration->getSecuredDirectoriesPattern(), $publicUrl);
    }

    public function getResourceUrl(string $publicUrl): string
    {
        $secureLinkFactory = GeneralUtility::makeInstance(SecureLinkFactory::class);
        $secureLinkFactory->setResourceUri(rawurldecode($publicUrl));

        return $secureLinkFactory->getUrl();
    }
}
