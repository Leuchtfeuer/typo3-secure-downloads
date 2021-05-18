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
    protected $extensionConfiguration;

    /**
     * @var string
     */
    protected $directoryPattern = '';

    /**
     * @var string
     */
    protected $fileTypePattern = '';

    /**
     * @var string
     */
    protected $domain = '';

    /**
     * @var int
     */
    protected $fileCount = 0;

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

    /**
     * @var array
     */
    protected $unprotectedFiles = [];

    public function __construct()
    {
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $this->directoryPattern = $this->extensionConfiguration->getSecuredDirectoriesPattern();
        $this->fileTypePattern = sprintf('#\.(%s)$#i', $this->extensionConfiguration->getSecuredFileTypes());
        $this->domain = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST');
    }

    /**
     * @return string The HTML content
     */
    public function render(): string
    {
        $this->setDirectories();

        if (!empty($this->unprotectedFiles)) {
            return $this->getFileErrorInfo();
        }

        // .htaccess check is only available for Apache web server
        if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') === 0) {
            $this->checkDirectories();

            if (!empty($this->unprotectedDirectories)) {
                return $this->getDirectoryWarningInfo();
            }
        }

        return $this->getConfigurationOkayInfo();
    }

    /**
     *
     */
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
     * @param Finder  $directories
     * @param string $publicDirectory
     */
    protected function getSuitableDirectories(Finder $directories, string $publicDirectory)
    {
        foreach ($directories as $directory) {
            $directoryPath = sprintf('%s/%s', $publicDirectory, $directory->getRelativePathname());
            if (preg_match($this->directoryPattern, $directoryPath)) {
                $realDirectoryPath = $directory->getRealPath();
                $this->directories[] = $realDirectoryPath;
                $this->checkFilesAccessibility($realDirectoryPath, $directoryPath);
            }
        }
    }

    /**
     * @param string $realDirectoryPath
     * @param string $directoryPath
     */
    protected function checkFilesAccessibility(string $realDirectoryPath, string $directoryPath): void
    {
        if ($this->fileCount < 20) {
            $fileFinder = (new Finder())->name($this->fileTypePattern)->in($realDirectoryPath)->depth(0);
            foreach ($fileFinder->files() as $file) {
                $publicUrl = sprintf('%s/%s/%s', $this->domain, $directoryPath, $file->getRelativePathname());
                $statusCode = (new Client())->request('HEAD', $publicUrl, ['http_errors' => false])->getStatusCode();

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

    /**
     *
     */
    protected function checkDirectories(): void
    {
        $lastSecuredDirectory = null;

        foreach ($this->directories as $directory) {
            if ($lastSecuredDirectory && strpos($directory, $lastSecuredDirectory) === 0) {
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

        if (isset($_SERVER['SERVER_SOFTWARE']) && strpos($_SERVER['SERVER_SOFTWARE'], 'Apache') === 0) {
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
