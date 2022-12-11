<?php

use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\SecureDownloads\Service\SecureDownloadService;
use PHPUnit\Framework\TestCase;

class SecureDownloadServiceTest extends TestCase
{
    /**
     * @test
     */
    public function someDirectoriesPatternTests()
    {
        $methods = get_class_methods(ExtensionConfiguration::class);
        $methods =  array_diff($methods, ['getSecuredDirectoriesPattern']);

        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();

        $directories = 'fileadmin/secure|typo3temp';

        $extensionConfiguration->expects(self::any())->method('getSecuredDirs')->willReturn($directories);

        $secureDownloadService = new SecureDownloadService($extensionConfiguration);

        self::assertTrue($secureDownloadService->folderShouldBeSecured('typo3temp'));
        self::assertTrue($secureDownloadService->folderShouldBeSecured('/typo3temp'));
        self::assertTrue($secureDownloadService->folderShouldBeSecured('fileadmin/secure'));
        self::assertTrue($secureDownloadService->folderShouldBeSecured('fileadmin/secure/something_else'));
        self::assertTrue($secureDownloadService->folderShouldBeSecured('/fileadmin/secure'));

        //not matchting

        self::assertFalse($secureDownloadService->folderShouldBeSecured('nomatch'));
        self::assertFalse($secureDownloadService->folderShouldBeSecured('fileadmin'));
        self::assertFalse($secureDownloadService->folderShouldBeSecured('/fileadmin-secure'));
        self::assertFalse($secureDownloadService->folderShouldBeSecured('fileadmin-secure'));

        self::assertTrue(true);
    }
}
