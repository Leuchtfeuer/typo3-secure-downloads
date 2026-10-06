<?php

/*
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\SecureDownloads\Tests\Unit\UserFunctions;

use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\SecureDownloads\UserFunctions\CheckConfiguration;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CheckConfigurationTest extends TestCase
{
    /**
     * Call protected/private method of a class.
     *
     * @param object &$object Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     * @throws \ReflectionException
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function testSomeDirectoriesPatternTests()
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $configuration = [
            'securedDirs' => 'fileadmin/secure|typo3temp',
        ];

        $this->invokeMethod($extensionConfiguration, 'setPropertiesFromConfiguration', [$configuration]);

        $checkConfiguration = new CheckConfiguration($extensionConfiguration);

        //matching

        self::assertTrue($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['typo3temp']));
        self::assertTrue($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['/typo3temp']));
        self::assertTrue($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['fileadmin/secure']));
        self::assertTrue($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['fileadmin/secure/something_else']));
        self::assertTrue($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['/fileadmin/secure']));

        //not matchting

        self::assertFalse($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['nomatch']));
        self::assertFalse($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['fileadmin']));
        self::assertFalse($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['/fileadmin-secure']));
        self::assertFalse($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['fileadmin-secure']));
    }

    public function testEmptyDirectoriesPatternTests()
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $configuration = [
            'securedDirs' => '',
        ];

        $this->invokeMethod($extensionConfiguration, 'setPropertiesFromConfiguration', [$configuration]);

        $checkConfiguration = new CheckConfiguration($extensionConfiguration);

        self::assertFalse($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['typo3temp']));
        self::assertFalse($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['/typo3temp']));
        self::assertFalse($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['fileadmin/secure']));
        self::assertFalse($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['fileadmin/secure/something_else']));
        self::assertFalse($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['/fileadmin/secure']));
        self::assertFalse($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['nomatch']));
        self::assertFalse($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['fileadmin']));
        self::assertFalse($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['/fileadmin-secure']));
        self::assertFalse($this->invokeMethod($checkConfiguration, 'isDirectoryMatching', ['fileadmin-secure']));
    }

    /**
     * @return array<string, array{string, array<string>}>
     */
    public static function securedFileTypesProvider(): array
    {
        return [
            'wildcard' => [ExtensionConfiguration::FILE_TYPES_WILDCARD, ['README', 'doc.pdf', 'notes.txt']],
            'regular expression for all files' => ['.*', ['README', 'doc.pdf', 'notes.txt']],
            'single file type' => ['pdf', ['doc.pdf']],
            'file types ignoring case' => ['PDF|txt', ['doc.pdf', 'notes.txt']],
        ];
    }

    /**
     * @param array<string> $expectedFileNames
     */
    #[DataProvider('securedFileTypesProvider')]
    public function testFindSecuredFilesMatchesFileExtensions(string $securedFileTypes, array $expectedFileNames)
    {
        $directory = sys_get_temp_dir() . '/' . uniqid('secure_downloads_', true);
        mkdir($directory);
        foreach (['README', 'doc.pdf', 'notes.txt'] as $fileName) {
            touch($directory . '/' . $fileName);
        }

        try {
            $checkConfiguration = new CheckConfiguration($this->getExtensionConfiguration(['securedFiletypes' => $securedFileTypes]));

            $foundFileNames = [];
            foreach ($this->invokeMethod($checkConfiguration, 'findSecuredFiles', [$directory]) as $file) {
                $foundFileNames[] = $file->getFilename();
            }
            sort($foundFileNames);

            self::assertSame($expectedFileNames, $foundFileNames);
        } finally {
            array_map('unlink', glob($directory . '/*'));
            rmdir($directory);
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function htaccessFileTypesProvider(): array
    {
        return [
            'wildcard' => [ExtensionConfiguration::FILE_TYPES_WILDCARD, '^[^.]*$|\\.(.*)$'],
            'regular expression for all files' => ['.*', '^[^.]*$|\\.(.*)$'],
            'default file types' => ['pdf|jpe?g', '\\.(pdf|jpe?g)$'],
        ];
    }

    #[DataProvider('htaccessFileTypesProvider')]
    public function testHtaccessExamplesMatchConfiguredFileTypes(string $securedFileTypes, string $expectedFilesMatchPattern)
    {
        $checkConfiguration = new CheckConfiguration($this->getExtensionConfiguration(['securedFiletypes' => $securedFileTypes]));

        self::assertStringContainsString(
            htmlspecialchars(sprintf('<FilesMatch "%s">', $expectedFilesMatchPattern)),
            $this->invokeMethod($checkConfiguration, 'getHtaccessExamples')
        );
    }

    /**
     * @param array<string, string> $configuration
     */
    private function getExtensionConfiguration(array $configuration): ExtensionConfiguration
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $this->invokeMethod($extensionConfiguration, 'setPropertiesFromConfiguration', [$configuration]);

        return $extensionConfiguration;
    }
}
