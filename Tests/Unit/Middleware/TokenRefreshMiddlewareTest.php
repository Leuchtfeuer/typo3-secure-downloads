<?php

namespace Bitmotion\SecureDownloads\Tests\Unit\Middleware;

use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Bitmotion\SecureDownloads\Factory\SecureLinkFactory;
use Bitmotion\SecureDownloads\Middleware\TokenRefreshMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Http\HtmlResponse;

class TokenRefreshMiddlewareTest extends TestCase
{
    public function setProtectedProperty($object, $property, $value)
    {
        $reflection = new \ReflectionClass($object);
        $reflection_property = $reflection->getProperty($property);
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($object, $value);
    }

    /**
     * @test
     */
    public function whenGroupCheckEnabledResponseBodyIsNotModified()
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfiguration->expects(self::any())->method('getDocumentRootPath')->willReturn('/');
        $extensionConfiguration->expects(self::any())->method('getLinkPrefix')->willReturn('securedl');
        $extensionConfiguration->expects(self::any())->method('getTokenPrefix')->willReturn('sdl-');
        $extensionConfiguration->expects(self::any())->method('isEnableGroupCheck')->willReturn(true);

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

    /**
     * @test
     */
    public function whenGroupCheckDisableAndNoUserLogInResponseBodyIsNotModified()
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfiguration->expects(self::any())->method('getDocumentRootPath')->willReturn('/');
        $extensionConfiguration->expects(self::any())->method('getLinkPrefix')->willReturn('securedl');
        $extensionConfiguration->expects(self::any())->method('getTokenPrefix')->willReturn('sdl-');
        $extensionConfiguration->expects(self::any())->method('isEnableGroupCheck')->willReturn(false);

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

    /**
     * @test
     */
    public function whenGroupCheckEnableAndNoUserLogInResponseBodyIsNotModified()
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfiguration->expects(self::any())->method('getDocumentRootPath')->willReturn('/');
        $extensionConfiguration->expects(self::any())->method('getLinkPrefix')->willReturn('securedl');
        $extensionConfiguration->expects(self::any())->method('getTokenPrefix')->willReturn('sdl-');
        $extensionConfiguration->expects(self::any())->method('isEnableGroupCheck')->willReturn(false);

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

    /**
     * @test
     */
    public function whenGroupCheckEnableAndUserLogInWithOutSecuredLinkResponseBodyIsNotModified()
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfiguration->expects(self::any())->method('getDocumentRootPath')->willReturn('/');
        $extensionConfiguration->expects(self::any())->method('getLinkPrefix')->willReturn('securedl');
        $extensionConfiguration->expects(self::any())->method('getTokenPrefix')->willReturn('sdl-');
        $extensionConfiguration->expects(self::any())->method('isEnableGroupCheck')->willReturn(false);

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
            ->willReturn(1);

        $tokenRefreshMiddleWare = new TokenRefreshMiddleware($extensionConfiguration, $context);

        $handler = $this->getMockBuilder(RequestHandlerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $response = new HtmlResponse('Test', 200);

        $handler->expects(self::any())
            ->method('handle')->willReturn($response);

        $returnResponse = $tokenRefreshMiddleWare->process($request, $handler);

        self::assertSame($response, $returnResponse);
    }

    /**
     * test
     */
    public function whenALinkWithTheSameUserIDofTheCurrentUserLinkResponseBodyIsNotModified()
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfiguration->expects(self::any())->method('getDocumentRootPath')->willReturn('/');
        $extensionConfiguration->expects(self::any())->method('getLinkPrefix')->willReturn('securedl');
        $extensionConfiguration->expects(self::any())->method('getTokenPrefix')->willReturn('sdl-');
        $extensionConfiguration->expects(self::any())->method('isEnableGroupCheck')->willReturn(false);

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
            ->setMethods(null)
            ->getMock();

        $this->setProtectedProperty($secureLinkFactory, 'extensionConfiguration', $extensionConfiguration);
        $this->setProtectedProperty($secureLinkFactory, 'userId', 1);
        $this->setProtectedProperty($secureLinkFactory, 'userGroups', [0, -2, 1]);
        $this->setProtectedProperty($secureLinkFactory, 'pageId', 1);
        $this->setProtectedProperty($secureLinkFactory, 'linkTimeout', time()+60);
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

    /**
     * @test
     */
    public function whenALinkWithAnOtherUserIDofTheCurrentUserLinkResponseBodyIsModified()
    {
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfiguration->expects(self::any())->method('getDocumentRootPath')->willReturn('/');
        $extensionConfiguration->expects(self::any())->method('getLinkPrefix')->willReturn('securedl');
        $extensionConfiguration->expects(self::any())->method('getTokenPrefix')->willReturn('sdl-');
        $extensionConfiguration->expects(self::any())->method('isEnableGroupCheck')->willReturn(false);

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
            ->setMethods(null)
            ->getMock();

        $this->setProtectedProperty($secureLinkFactory, 'extensionConfiguration', $extensionConfiguration);
        $this->setProtectedProperty($secureLinkFactory, 'userId', 1);
        $this->setProtectedProperty($secureLinkFactory, 'userGroups', [0, -2, 1]);
        $this->setProtectedProperty($secureLinkFactory, 'pageId', 1);
        $this->setProtectedProperty($secureLinkFactory, 'linkTimeout', time()+60);
        $this->setProtectedProperty($secureLinkFactory, 'resourceUri', 'fileadmin/foo.txt');

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 'secret';

        $url = $secureLinkFactory->getUrl();

        $content = '<a href="/' . $url . '">foo.txt</a>';

        $response = new HtmlResponse($content, 200);

        $this->setProtectedProperty($secureLinkFactory, 'userId', 2);

        $expectedUrl = $secureLinkFactory->getUrl();

        self::assertTrue($expectedUrl != $url);

        $expectedContent = '<a href="/' . $expectedUrl . '">foo.txt</a>';

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
