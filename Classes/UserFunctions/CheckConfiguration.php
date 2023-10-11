<?php

declare(strict_types=1);
namespace Leuchtfeuer\SecureDownloads\UserFunctions;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use GuzzleHttp\Client;
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
    protected mixed $extensionConfiguration;

    /**
     * @var string
     */
    protected string $directoryPattern = '';

    /**
     * @var string
     */
    protected string $fileTypePattern = '';

    /**
     * @var string
     */
    protected string $domain = '';

    /**
     * @var int
     */
    protected int $fileCount = 0;

    /**
     * @var array
     */
    protected array $directories = [];

    /**
     * @var array
     */
    protected array $unprotectedDirectories = [];

    /**
     * @var array
     */
    protected array $protectedDirectories = [];

    /**
     * @var array
     */
    protected array $unprotectedFiles = [];

    /**
     * @param ExtensionConfiguration|null $extensionConfiguration
     */
    public function __construct(?ExtensionConfiguration $extensionConfiguration = null)
    {
        if ($extensionConfiguration === null) {
            $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        }
        $this->extensionConfiguration = $extensionConfiguration;
        $this->directoryPattern = $this->extensionConfiguration->getSecuredDirectoriesPattern();
        $this->fileTypePattern = sprintf('#\.(%s)$#i', $this->extensionConfiguration->getSecuredFileTypes());
        $this->domain = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
    }

    /**
     * @return string The HTML content
     */
    public function renderCheckAccess(): string
    {
        if ($this->extensionConfiguration->isSkipCheckConfiguration()) {
            return 'Check skipped as the option "backend.skipCheckConfiguration" is active';
        }

        $this->setDirectories();

        if (!empty($this->unprotectedFiles)) {
            return $this->getFileErrorInfo();
        }

        // .htaccess check is only available for Apache web server
        if (isset($_SERVER['SERVER_SOFTWARE']) && str_starts_with($_SERVER['SERVER_SOFTWARE'], 'Apache')) {
            $this->checkDirectories();

            if (!empty($this->unprotectedDirectories)) {
                return $this->getDirectoryWarningInfo();
            }
        }

        return $this->getConfigurationOkayInfo();
    }

    /**
     * @return string
     */
    public function renderCheckDirs(): string
    {
        if ($this->extensionConfiguration->isSkipCheckConfiguration()) {
            return 'Check skipped as the option "backend.skipCheckConfiguration" is active';
        }

        $this->setDirectories();

        if (count($this->protectedDirectories) === 0) {
            return $this->noSecuredDirectoryFoundWarningInfo();
        }
        return $this->securedDirectoryFoundOkayInfo();
    }

    protected function isDirectoryMatching(string $directoryPath): bool
    {
        $result = preg_match($this->directoryPattern, $directoryPath) === 1;

        if (!$result && str_starts_with($directoryPath, '/')) {
            return $this->isDirectoryMatching(substr($directoryPath, 1));
        }

        return $result;
    }

    protected function setDirectories(): void
    {
        foreach ($this->getPublicDirectories() as $publicDirectory) {
            $path = sprintf('%s/%s', Environment::getPublicPath(), $publicDirectory);
            $finder = (new Finder())->directories();
            $directories = $finder->in($path);
            $this->getSuitableDirectories($directories, $publicDirectory);
        }
    }

    /**
     * @param Finder $directories
     * @param string $publicDirectory
     */
    protected function getSuitableDirectories(Finder $directories, string $publicDirectory): void
    {
        foreach ($directories as $directory) {
            $directoryPath = sprintf('%s/%s', $publicDirectory, $directory->getRelativePathname());
            if ($this->isDirectoryMatching($directoryPath)) {
                $realDirectoryPath = $directory->getRealPath();
                if (!$realDirectoryPath) {
                    continue;
                }
                $this->directories[] = $realDirectoryPath;
                $this->checkFilesAccessibility($realDirectoryPath, $directoryPath);
            }
            if ($this->fileCount >= 20) {
                break;
            }
        }
    }

    /**
     * @param string $realDirectoryPath
     * @param string $directoryPath
     */
    protected function checkFilesAccessibility(string $realDirectoryPath, string $directoryPath): void
    {
        $fileFinder = (new Finder())->name($this->fileTypePattern)->in($realDirectoryPath)->depth(0);
        foreach ($fileFinder->files() as $file) {
            $publicUrl = sprintf('%s/%s/%s', $this->domain, $directoryPath, $file->getRelativePathname());
            $verify = $GLOBALS['TYPO3_CONF_VARS']['HTTP']['verify'];
            $statusCode = (new Client())->request(
                'HEAD',
                $publicUrl,
                ['http_errors' => false, 'verify' => filter_var($verify, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $verify ?? true]
            )->getStatusCode();

            if ($statusCode !== 403) {
                $this->fileCount++;
                $this->unprotectedFiles[] = [
                    'url' => $publicUrl,
                    'statusCode' => $statusCode,
                ];

                if ($this->fileCount >= 20) {
                    break;
                }
            }
        }
    }

    /**
     * @return array
     */
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
            if ($lastSecuredDirectory && str_starts_with($directory, $lastSecuredDirectory)) {
                continue;
            }

            $finder = (new Finder())->files()->ignoreDotFiles(false)->name('.htaccess')->depth(0);

            foreach ($finder->in($directory)->getIterator() as $file) {
                $lastSecuredDirectory = $directory;
                $this->protectedDirectories[] = $directory;
                continue 2;
            }

            $this->unprotectedDirectories[] = $directory;
        }
    }

    /**
     * @return string
     */
    protected function getConfigurationOkayInfo(): string
    {
        return $this->getOutput(
            'success',
            'check',
            'You are all set 😀',
            'A .htaccess file exists in all configured directories, which should be secured.'
        );
    }

    /**
     * @return string
     */
    protected function getFileErrorInfo(): string
    {
        return $this->getOutput(
            'danger',
            'times',
            'You are not safe 🤯',
            $this->getFileErrorContent()
        );
    }

    /**
     * @return string
     */
    protected function getDirectoryWarningInfo(): string
    {
        return $this->getOutput(
            'warning',
            'times',
            'Your system might be insecure 🤕',
            $this->getDirectoryErrorContent()
        );
    }

    /**
     * @return string
     */
    protected function securedDirectoryFoundOkayInfo(): string
    {
        return $this->getOutput(
            'success',
            'check',
            'You have a least one protected directory 😀',
            implode('<br>', $this->protectedDirectories)
        );
    }

    /**
     * @return string
     */
    protected function noSecuredDirectoryFoundWarningInfo(): string
    {
        return $this->getOutput(
            'warning',
            'times',
            'Your system might be insecure 🤕',
            'No directory found that matches the search pattern.'
        );
    }

    /**
     * @return string
     */
    protected function getFileErrorContent(): string
    {
        $files = array_slice($this->unprotectedFiles, 0, 10);

        array_walk($files, function (&$item, $key) {
            $item = sprintf(
                '<li><code>%s</code><br/>Returned status code: <strong>%d</strong> (expected: 403).</li>',
                $item['url'],
                $item['statusCode']
            );
        });

        $content = sprintf(
            'There are files publicly available which should be secured:<ul>%s</ul>',
            implode($files)
        );

        if (count($this->unprotectedFiles) > 10) {
            $content .= '<p>Only the first ten results are shown.</p>';
        }

        if (isset($_SERVER['SERVER_SOFTWARE']) && str_starts_with($_SERVER['SERVER_SOFTWARE'], 'Apache')) {
            $content .= '<p>Here is some example code which can be used depending on your Apache version:</p>';
            $content .= $this->getHtaccessExamples();
        }

        return $content;
    }

    /**
     * @return string
     */
    protected function getDirectoryErrorContent(): string
    {
        $directories = array_slice($this->unprotectedDirectories, 0, 10);

        array_walk($directories, function (&$item, $key) {
            $item = '<li><code>' . $item . '</code></li>';
        });

        $content = '<p>There is at least one .htaccess file missing. If there is an .htaccess file in a parent directory, you '
            . 'can ignore this message.</p>'
            . '<p>Here is some example code which can be used depending on your Apache version. In addition, code examples can be '
            . 'found in this extension underneath the <code>Resources/Private/Examples</code> folder.</p>';

        $content .= sprintf(
            '<p>%s</p>Please check these directories:<ul>%s</ul>',
            $this->getHtaccessExamples(),
            implode($directories)
        );

        if (count($this->unprotectedDirectories) > 10) {
            $content .= '<p>Only the first ten results are shown.</p>';
        }

        return $content;
    }

    /**
     * @param string $type
     * @param string $icon
     * @param string $title
     * @param string $content
     * @return string
     */
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

    /**
     * @return string
     */
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
