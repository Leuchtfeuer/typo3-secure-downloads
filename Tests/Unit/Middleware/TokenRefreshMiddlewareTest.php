<?php

/*
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\SecureDownloads\Tests\Unit\Middleware;

use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\DefaultToken;
use Leuchtfeuer\SecureDownloads\Factory\SecureLinkFactory;
use Leuchtfeuer\SecureDownloads\Middleware\TokenRefreshMiddleware;
use Leuchtfeuer\SecureDownloads\Registry\TokenRegistry;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Http\HtmlResponse;

class TokenRefreshMiddlewareTest extends TestCase
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
     * @throws \ReflectionException
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

    public function setPrivateProperty(string $className, object $object, string $property, $value)
    {
        $reflectionProperty = new \ReflectionProperty($className, $property);
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($object, $value);
    }

    public function setProtectedProperty($object, $property, $value)
    {
        $reflection = new \ReflectionClass($object);
        $reflection_property = $reflection->getProperty($property);
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($object, $value);
    }

    public function testWhenGroupCheckEnabledResponseBodyIsNotModified()
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $configuration = [
            'securedDirs' => 'fileadmin/secure|typo3temp',
            'linkPrefix' => 'securedl',
            'tokenPrefix' => 'sdl-',
            'documentRootPath' => '/',
            'enableGroupCheck' => 1,
        ];

        $this->invokeMethod($extensionConfiguration, 'setPropertiesFromConfiguration', [$configuration]);

        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokenRefreshMiddleWare = new TokenRefreshMiddleware($extensionConfiguration, $context);

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response->expects(self::never())->method('getBody');

        $handler->expects(self::once())
            ->method('handle')->willReturn($response);

        $tokenRefreshMiddleWare->process($request, $handler);
    }

    public function testWhenGroupCheckDisableAndNoUserLogInResponseBodyIsNotModified()
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $configuration = [
            'securedDirs' => 'fileadmin/secure|typo3temp',
            'linkPrefix' => 'securedl',
            'tokenPrefix' => 'sdl-',
            'documentRootPath' => '/',
            'enableGroupCheck' => 0,
        ];

        $this->invokeMethod($extensionConfiguration, 'setPropertiesFromConfiguration', [$configuration]);

        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $tokenRefreshMiddleWare = new TokenRefreshMiddleware($extensionConfiguration, $context);

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response->expects(self::never())->method('getBody');

        $handler->expects(self::once())
            ->method('handle')->willReturn($response);

        $tokenRefreshMiddleWare->process($request, $handler);
    }

    public function testWhenGroupCheckEnableAndNoUserLogInResponseBodyIsNotModified()
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $configuration = [
            'securedDirs' => 'fileadmin/secure|typo3temp',
            'linkPrefix' => 'securedl',
            'tokenPrefix' => 'sdl-',
            'documentRootPath' => '/',
            'enableGroupCheck' => 0,
        ];

        $this->invokeMethod($extensionConfiguration, 'setPropertiesFromConfiguration', [$configuration]);

        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $currentUser = $this->getMockBuilder(UserAspect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects(self::once())
            ->method('getAspect')
            ->with('frontend.user')
            ->willReturn($currentUser);

        $currentUser->expects(self::once())
            ->method('get')
            ->with('id')
            ->willReturn(0);

        $tokenRefreshMiddleWare = new TokenRefreshMiddleware($extensionConfiguration, $context);

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response = new HtmlResponse('Test', 200);

        $handler->expects(self::once())
            ->method('handle')->willReturn($response);

        $returnResponse = $tokenRefreshMiddleWare->process($request, $handler);

        self::assertSame($response, $returnResponse);
    }

    public function testWhenNotGroupCheckEnableAndUserLogInWithOutSecuredLinkResponseBodyIsNotModified()
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $configuration = [
            'securedDirs' => 'fileadmin/secure|typo3temp',
            'linkPrefix' => 'securedl',
            'tokenPrefix' => 'sdl-',
            'documentRootPath' => '/',
            'enableGroupCheck' => 0,
        ];

        $this->invokeMethod($extensionConfiguration, 'setPropertiesFromConfiguration', [$configuration]);

        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $currentUser = $this->getMockBuilder(UserAspect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects(self::any())
            ->method('getAspect')
            ->with('frontend.user')
            ->willReturn($currentUser);

        $currentUser->expects(self::once())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $tokenRefreshMiddleWare = new TokenRefreshMiddleware($extensionConfiguration, $context);

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $content = '<h1>Test</h1>';
        $response = new HtmlResponse($content, 200);

        $handler->expects(self::any())
            ->method('handle')->willReturn($response);

        $returnResponse = $tokenRefreshMiddleWare->process($request, $handler);

        $body = $returnResponse->getBody();
        $body->rewind();
        $returnContent = $body->getContents();

        self::assertSame($content, $returnContent);
    }

    /**
     * test
     * @TODO: Check Test
     */
    public function whenALinkWithTheSameUserIDofTheCurrentUserLinkResponseBodyIsNotModified()
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $configuration = [
            'securedDirs' => 'fileadmin/secure|typo3temp',
            'linkPrefix' => 'securedl',
            'tokenPrefix' => 'sdl-',
            'documentRootPath' => '/',
            'enableGroupCheck' => 0,
        ];

        $this->invokeMethod($extensionConfiguration, 'setPropertiesFromConfiguration', [$configuration]);

        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $currentUser = $this->getMockBuilder(UserAspect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects(self::once())
            ->method('getAspect')
            ->with('frontend.user')
            ->willReturn($currentUser);

        $currentUser->expects(self::any())
            ->method('get')
            ->with('id')
            ->willReturn(1);

        $currentUser->expects(self::any())
            ->method('getGroupIds')
            ->willReturn([0, -2 , 1]);

        $tokenRefreshMiddleWare = new TokenRefreshMiddleware($extensionConfiguration, $context);

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $secureLinkFactory = $this->getMockBuilder(SecureLinkFactory::class)
            ->disableOriginalConstructor()
//            ->setMethods(null)
            ->getMock();

        $this->setProtectedProperty($secureLinkFactory, 'extensionConfiguration', $extensionConfiguration);
        $this->setProtectedProperty($secureLinkFactory, 'userId', 1);
        $this->setProtectedProperty($secureLinkFactory, 'userGroups', [0, -2, 1]);
        $this->setProtectedProperty($secureLinkFactory, 'pageId', 1);
        $this->setProtectedProperty($secureLinkFactory, 'linkTimeout', time() + 60);
        $this->setProtectedProperty($secureLinkFactory, 'resourceUri', 'fileadmin/foo.txt');

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'secret';

        $url = $secureLinkFactory->getUrl();

        $content = '<a href="' . $url . '">foo.txt</a>';

        $response = new HtmlResponse($content, 200);

        $handler->expects(self::any())
            ->method('handle')->willReturn($response);

        $returnResponse = $tokenRefreshMiddleWare->process($request, $handler);

        $body = $returnResponse->getBody();
        $body->rewind();
        $returnContent = $body->getContents();

        self::assertSame($response, $returnResponse);
        self::assertSame($content, $returnContent);
    }

    public function testWhenALinkWithAnOtherUserIDofTheCurrentUserLinkResponseBodyIsModified()
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();

        $configuration = [
            'securedDirs' => 'fileadmin/secure|typo3temp',
            'linkPrefix' => 'securedl',
            'tokenPrefix' => 'sdl-',
            'documentRootPath' => '/',
            'enableGroupCheck' => 0,
        ];

        $this->invokeMethod($extensionConfiguration, 'setPropertiesFromConfiguration', [$configuration]);

        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $currentUser = $this->getMockBuilder(UserAspect::class)
            ->disableOriginalConstructor()
            ->getMock();

        $context->expects(self::any())
            ->method('getAspect')
            ->with('frontend.user')
            ->willReturn($currentUser);

        $currentUser->expects(self::any())
            ->method('get')
            ->with('id')
            ->willReturn(2);

        $currentUser->expects(self::any())
            ->method('getGroupIds')
            ->willReturn([0, -2 , 1]);

        $tokenRefreshMiddleWare = new TokenRefreshMiddleware($extensionConfiguration, $context);

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $secureLinkFactory = $this->getMockBuilder(SecureLinkFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['dispatchEnrichPayloadEvent'])
            ->getMock();

        $this->setPrivateProperty(SecureLinkFactory::class, $secureLinkFactory, 'extensionConfiguration', $extensionConfiguration);

        TokenRegistry::register(
            'tx_securedownloads_default',
            DefaultToken::class,
            0,
            false
        );
        $token = TokenRegistry::getToken();
        $this->setPrivateProperty(SecureLinkFactory::class, $secureLinkFactory, 'token', $token);

        $secureLinkFactory->withUser(1)
            ->withGroups([0, -2, 1])
            ->withPage(1)
            ->withLinkTimeout(time() + 60)
            ->withResourceUri('fileadmin/secure/document.pdf');

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'my-secret';

        $url = $secureLinkFactory->getUrl();

        $content = '<a href="/' . $url . '">Document</a>';

        $response = new HtmlResponse($content, 200);

        $secureLinkFactory->withUser(2);

        $expectedUrl = $secureLinkFactory->getUrl();

        self::assertTrue($expectedUrl != $url);

        $expectedContent = '<a href="/' . $expectedUrl . '">Document</a>';

        self::assertTrue($expectedContent != $content);

        $handler->expects(self::any())
            ->method('handle')
            ->willReturn($response);

        $returnResponse = $tokenRefreshMiddleWare->process($request, $handler);

        $body = $returnResponse->getBody();
        $body->rewind();
        $returnedContent = $body->getContents();

        self::assertSame($expectedContent, $returnedContent);
    }
}
