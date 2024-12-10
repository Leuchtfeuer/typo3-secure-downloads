<?php

/*
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\SecureDownloads\Service\SecureDownloadService;
use PHPUnit\Framework\TestCase;

class SecureDownloadServiceTest extends TestCase
{
    /**
     * Call protected/private method of a class.
     *
     * @param object &$object Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     * @throws ReflectionException
     * @throws ReflectionException
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
            'securedFiletypes' => 'pdf|jpe?g|gif|png|odt|pptx?|docx?|xlsx?|zip|rar|tgz|tar|gz',
        ];

        $this->invokeMethod($extensionConfiguration, 'setPropertiesFromConfiguration', [$configuration]);

        $secureDownloadService = new SecureDownloadService($extensionConfiguration);

        // matching

        self::assertTrue($secureDownloadService->folderShouldBeSecured('typo3temp'));
        self::assertTrue($secureDownloadService->folderShouldBeSecured('/typo3temp'));
        self::assertTrue($secureDownloadService->folderShouldBeSecured('fileadmin/secure'));
        self::assertTrue($secureDownloadService->folderShouldBeSecured('fileadmin/secure/something_else'));
        self::assertTrue($secureDownloadService->folderShouldBeSecured('/fileadmin/secure'));

        // not matching

        self::assertFalse($secureDownloadService->folderShouldBeSecured('nomatch'));
        self::assertFalse($secureDownloadService->folderShouldBeSecured('fileadmin'));
        self::assertFalse($secureDownloadService->folderShouldBeSecured('/fileadmin-secure'));
        self::assertFalse($secureDownloadService->folderShouldBeSecured('fileadmin-secure'));
    }

    public function testEmptyDirectoriesPatternTests()
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $configuration = [
            'securedDirs' => '',
            'securedFiletypes' => 'pdf|jpe?g|gif|png|odt|pptx?|docx?|xlsx?|zip|rar|tgz|tar|gz',
        ];

        $this->invokeMethod($extensionConfiguration, 'setPropertiesFromConfiguration', [$configuration]);

        $secureDownloadService = new SecureDownloadService($extensionConfiguration);

        // matching

        self::assertFalse($secureDownloadService->folderShouldBeSecured('typo3temp'));
        self::assertFalse($secureDownloadService->folderShouldBeSecured('/typo3temp'));
        self::assertFalse($secureDownloadService->folderShouldBeSecured('fileadmin/secure'));
        self::assertFalse($secureDownloadService->folderShouldBeSecured('fileadmin/secure/something_else'));
        self::assertFalse($secureDownloadService->folderShouldBeSecured('/fileadmin/secure'));
        self::assertFalse($secureDownloadService->folderShouldBeSecured('nomatch'));
        self::assertFalse($secureDownloadService->folderShouldBeSecured('fileadmin'));
        self::assertFalse($secureDownloadService->folderShouldBeSecured('/fileadmin-secure'));
        self::assertFalse($secureDownloadService->folderShouldBeSecured('fileadmin-secure'));
    }

    public function testSomeFileTypesTests()
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $configuration = [
            'securedDirs' => 'fileadmin/secure|typo3temp',
            'securedFiletypes' => 'pdf|jpe?g|gif|png|odt|pptx?|docx?|xlsx?|zip|rar|tgz|tar|gz',
        ];

        $this->invokeMethod($extensionConfiguration, 'setPropertiesFromConfiguration', [$configuration]);

        $secureDownloadService = new SecureDownloadService($extensionConfiguration);

        // matching

        self::assertTrue($secureDownloadService->pathShouldBeSecured('fileadmin/secure/image.jpg'));
        self::assertTrue($secureDownloadService->pathShouldBeSecured('/fileadmin/secure/image.png'));
        self::assertTrue($secureDownloadService->pathShouldBeSecured('/fileadmin/secure/documents/table.xls'));
        self::assertTrue($secureDownloadService->pathShouldBeSecured('/typo3temp/foo/bar/doc.pdf'));

        // not matching

        self::assertFalse($secureDownloadService->pathShouldBeSecured('fileadmin/unsecure/image.jpg'));
        self::assertFalse($secureDownloadService->pathShouldBeSecured('fileadmin/content/secure/image.jpg'));
        self::assertFalse($secureDownloadService->pathShouldBeSecured('/fileadmin/unsecure/image.jpg'));
        self::assertFalse($secureDownloadService->pathShouldBeSecured('/fileadmin/secure/text.txt'));
        self::assertFalse($secureDownloadService->pathShouldBeSecured('fileadmin/secure/text.txt'));
        self::assertFalse($secureDownloadService->pathShouldBeSecured('fileadmin/text.txt'));
    }
}
