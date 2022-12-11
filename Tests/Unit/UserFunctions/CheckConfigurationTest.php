<?php

namespace Leuchtfeuer\SecureDownloads\Tests\Unit\UserFunctions;

use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\SecureDownloads\UserFunctions\CheckConfiguration;
use PHPUnit\Framework\TestCase;

class CheckConfigurationTest extends TestCase
{
    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

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
}
