<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\UserFunctions;

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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CheckConfiguration
{
    /**
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

    /**
     * @var array
     */
    protected $directories = [];

    /**
     * @var array
     */
    protected $missingDirectories = [];

    public function __construct()
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
    }

    public function render(): string
    {
        if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') === 0) {
            $this->setDirectories();

            if ($this->hasMissingDirectories()) {
                return $this->getMissingDirectoriesInfo();
            }

            if ($this->directoriesAreProtected()) {
                return $this->getConfigurationOkayInfo();
            }

            return $this->getConfigurationErrorInfo();
        }

        return $this->getNotSupportedInfo();
    }

    protected function setDirectories(): void
    {
        $securedDirectories = GeneralUtility::trimExplode('|', $this->extensionConfiguration->getSecuredDirs(), true);
        $rootDirectory = Environment::getPublicPath() . '/';

        foreach ($securedDirectories as $securedDirectory) {
            $absolutePath = $rootDirectory . trim($securedDirectory, '/');
            if (@is_dir($absolutePath)) {
                $this->directories[] = $absolutePath;
            } else {
                $this->missingDirectories[] = $absolutePath;
            }
        }
    }

    protected function hasMissingDirectories(): bool
    {
        return !empty($this->missingDirectories);
    }

    protected function directoriesAreProtected(): bool
    {
        foreach ($this->directories as $securedDirectory) {
            $filePath = sprintf('%s/.htaccess', $securedDirectory);
            if (!@file_exists($filePath)) {
                return false;
            }
        }

        return true;
    }

    protected function getConfigurationOkayInfo(): string
    {
        return $this->getOutput(
            'success',
            'check',
            'You are all set 😀',
            'A .htaccess file exists in all configured directories, which should be secured.'
        );
    }

    protected function getConfigurationErrorInfo(): string
    {
        array_walk($this->directories, function (&$item, $key) {
            $item = '<li>' . $item . '</li>';
        });

        $content = sprintf('<p>There is at least one .htaccess file missing. Please add this files to your filesystem.<br/>You can find some Examples in the EXT:secure_downloads/Resources/Private/Examples directory.</p>Please check these directories:<ul>%s</ul>', implode($this->directories));

        return $this->getOutput(
            'danger',
            'times',
            'You are not safe 🤯',
            $content
        );
    }

    protected function getNotSupportedInfo(): string
    {
        return $this->getOutput(
            'info',
            'info',
            'Not supported web server 😪',
            'Only Apache web server are supported for now. There is no check for proper access control available.'
        );
    }

    protected function getMissingDirectoriesInfo(): string
    {
        array_walk($this->missingDirectories, function (&$item, $key) {
            $item = '<li>' . $item . '</li>';
        });

        $content = sprintf('Following directories are not present:<ul>%s</ul>', implode($this->missingDirectories));

        return $this->getOutput(
            'warning',
            'warning',
            'There are some directories missing 🤕',
            $content
        );
    }

    protected function getOutput(string $type, string $icon, string $title, string $content): string
    {
        return <<<HTML
<div class="callout callout-$type">
    <div class="media">
        <div class="media-left">
            <span class="fa-stack fa-lg callout-icon">
                <i class="fa fa-circle fa-stack-2x"></i>
                <i class="fa fa-$icon fa-stack-1x"></i>
            </span>
        </div>
        <div class="media-body">
            <h4 class="callout-title">$title</h4>
            <div class="callout-body">$content</div>
        </div>
    </div>
</div>
HTML;
    }
}
