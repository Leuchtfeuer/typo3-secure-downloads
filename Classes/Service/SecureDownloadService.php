<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Service;

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

use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Bitmotion\SecureDownloads\Factory\SecureLinkFactory;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SecureDownloadService implements SingletonInterface
{
    protected $extensionConfiguration;

    protected $securedFileTypesPattern;

    protected $securedDirectoriesPattern;

    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $this->extensionConfiguration = $extensionConfiguration;
        $this->securedFileTypesPattern = sprintf('/^(%s)$/i', $this->extensionConfiguration->getSecuredFileTypes());
        $this->securedDirectoriesPattern = sprintf('/^(%s)/i', str_replace('/', '\/', $this->extensionConfiguration->getSecuredDirs()));
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
            if (preg_match($this->securedFileTypesPattern, $fileExtension)) {
                return true;
            }
        }

        return false;
    }

    public function folderShouldBeSecured(string $publicUrl): bool
    {
        return (bool)preg_match($this->securedDirectoriesPattern, $publicUrl);
    }

    public function getResourceUrl(string $publicUrl): string
    {
        $secureLinkFactory = GeneralUtility::makeInstance(SecureLinkFactory::class, rawurldecode($publicUrl));

        return $secureLinkFactory->getUrl();
    }
}
