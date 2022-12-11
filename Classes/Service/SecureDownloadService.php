<?php

declare(strict_types=1);
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
     *
     * @param string $publicUrl The public (non-secured) URL to the file
     *
     * @return bool True, if the path of the file matches the configured configuration or the file is stored in a Secure Downloads
     *              file storage.
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

    /**
     * Checks whether secured folder matches secured directories pattern.
     *
     * @param string $publicUrl The public (non-secured) URL to the file
     *
     * @return bool True, if the path of the folder matches the configured configuration or the folder is part of a Secure
     *              Downloads file storage.
     */
    public function folderShouldBeSecured(string $publicUrl): bool
    {
        $pattern = $this->extensionConfiguration->getSecuredDirectoriesPattern();

        $result = (bool)preg_match($pattern, rtrim($publicUrl, '/'));

        if (!$result && substr($publicUrl, 0, 1) === '/') {
            return $this->folderShouldBeSecured(substr($publicUrl, 1));
        }

        return $result;
    }

    /**
     * Helper method for transforming a public URL into a secured URL.
     *
     * @param string $publicUrl The public (non-secured) URL to the file
     *
     * @return string The secured URL
     */
    public function getResourceUrl(string $publicUrl): string
    {
        $secureLinkFactory = GeneralUtility::makeInstance(SecureLinkFactory::class);

        return $secureLinkFactory->withResourceUri(rawurldecode($publicUrl))->getUrl();
    }
}
