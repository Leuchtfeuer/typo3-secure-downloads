<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\UserFunctions;

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
use Symfony\Component\Finder\Finder;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class CheckConfiguration implements SingletonInterface
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
    protected $unprotectedDirectories = [];

    /**
     * @var array
     */
    protected $protectedDirectories = [];

    public function __construct()
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
    }

    public function render(): string
    {
        if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') === 0) {
            $this->setDirectories();
            $this->checkDirectories();

            if (!empty($this->unprotectedDirectories)) {
                return $this->getConfigurationErrorInfo();
            }

            $this->checkProtectedDirectories();

            if (!empty($this->protectedDirectories)) {
                return $this->getConfigurationWarningInfo();
            }

            return $this->getConfigurationOkayInfo();
        }

        return $this->getNotSupportedInfo();
    }

    protected function setDirectories(): void
    {
        $directoryPattern = sprintf('#^(%s)#i', $this->extensionConfiguration->getSecuredDirs());

        foreach ($this->getPublicDirectories() as $publicDirectory) {
            $path = sprintf('%s/%s', Environment::getPublicPath(), $publicDirectory);
            $finder = (new Finder())->directories();

            foreach ($finder->in($path) as $directory) {
                $directoryPath = sprintf('%s/%s', $publicDirectory, $directory->getRelativePathname());
                if (preg_match($directoryPattern, $directoryPath)) {
                    $this->directories[] = sprintf('%s/%s', Environment::getPublicPath(), $directoryPath);
                }
            }
        }
    }

    protected function getPublicDirectories(): array
    {
        $publicDirectories = scandir(Environment::getPublicPath());

        return array_filter($publicDirectories, function ($directory) {
            return is_dir(sprintf('%s/%s', Environment::getPublicPath(), $directory)) && !in_array($directory, ['.', '..', 'typo3', 'typo3conf']);
        });
    }

    protected function checkDirectories(): void
    {
        $lastSecuredDirectory = null;

        foreach ($this->directories as $directory) {
            if ($lastSecuredDirectory && strpos($directory, $lastSecuredDirectory) === 0) {
                continue;
            }

            $finder = (new Finder())->files()->ignoreDotFiles(false)->name('.htaccess')->depth(0);

            foreach ($finder->in($directory) as $file) {
                $lastSecuredDirectory = $directory;
                $this->protectedDirectories[] = $directory;
                continue 2;
            }

            $this->unprotectedDirectories[] = $directory;
        }
    }

    protected function checkProtectedDirectories()
    {
        $secureFileTypes = $this->extensionConfiguration->getSecuredFileTypes();

        foreach ($this->protectedDirectories as $key => $directory) {
            $file = $directory . '/.htaccess';
            if (strpos(file_get_contents($file), $secureFileTypes) !== false) {
                unset($this->protectedDirectories[$key]);
            }
        }
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
        $directories = array_slice($this->directories, 0, 10);

        array_walk($directories, function (&$item, $key) {
            $item = '<li><code>' . $item . '</code></li>';
        });

        $content = sprintf(
            '<p>There is at least one .htaccess file missing. Please add this files to your filesystem.<br/>'
            . 'Here is some example code which can be used depending on your Apache version. In addition, code examples can be '
            . 'found in this extension underneath the <code>Resources/Private/Examples</code> folder.</p>%s</p>Please check '
            . 'these directories:<ul>%s</ul>',
            $this->getHtaccessExamples(),
            implode($directories)
        );

        if (count($this->directories) > 10) {
            $content .= '<p>Only the first ten results are shown.</p>';
        }

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

    protected function getConfigurationWarningInfo(): string
    {
        array_walk($this->protectedDirectories, function (&$item, $key) {
            $item = '<li><code>' . $item . '</code></li>';
        });

        $content = sprintf(
            '<p>An <code>.htaccess</code> file was found in the following folders, but the file does not contain the '
            . '<code>parsing.securedFiletypes</code>:<ul>%s</ul><p>You can safely ignore this message if you have checked '
            . 'contents of the file.</p>',
            implode($this->protectedDirectories)
        );

        return $this->getOutput(
            'warning',
            'warning',
            'Files may be accessible 🤕',
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

    protected function getHtaccessExamples(): string
    {
        $fileTypes = $this->extensionConfiguration->getSecuredFileTypes();

        $code = <<<HTACCESS
# Apache 2.4
<IfModule mod_authz_core.c>
    <FilesMatch "\.($fileTypes)$">
        Require all denied
    </FilesMatch>
</IfModule>

# Apache 2.2
<IfModule !mod_authz_core.c>
    <FilesMatch "\.($fileTypes)$">
        Order Allow,Deny
        Deny from all
    </FilesMatch>
</IfModule>
HTACCESS;

        return sprintf('<pre>%s</pre>', htmlspecialchars($code));
    }
}
