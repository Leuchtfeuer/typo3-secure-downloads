<?php

declare(strict_types=1);

/*
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\SecureDownloads\Tests\Functional\Middleware;

use Firebase\JWT\JWT;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\SecureDownloads\Middleware\TokenRefreshMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TokenRefreshMiddlewareTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/secure_downloads',
    ];

    protected string $assetPrefix;
    protected RequestHandlerInterface $requestHandler;
    protected Context $context;

    protected function setUp(): void
    {
        parent::setUp();

        $tokenSecret = '727fdcee031b34f1bdf45ae5fda62b032f4cfb61f8f0a4313443d55948ba9654fc193b827381576abc7a87dc7ac2c247';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = $tokenSecret;

        $this->requestHandler = $this->createRequestHandler(456);

        $this->assetPrefix = sprintf(
            '%s%s/%s',
            '/',
            'securedl',
            'sdl-'
        );
    }

    public function testProcessShouldSkipTokenRefreshWhenGroupCheckIsEnabled(): void
    {

        /** @var ExtensionConfiguration&\PHPUnit\Framework\MockObject\MockObject $extensionConfiguration */
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfiguration->method('isEnableGroupCheck')->willReturn(true);

        $tokenRefreshMiddleware = new TokenRefreshMiddleware($extensionConfiguration);
        $request = new ServerRequest('https://example.org', 'GET');
        $expected = $this->requestHandler->handle($request);

        $response = $tokenRefreshMiddleware->process($request, $this->requestHandler);

        self::assertSame(200, $response->getStatusCode());
        self::assertEquals($expected->getBody()->getContents(), $response->getBody()->getContents());
    }

    public function testProcessShouldRefreshTokenWhenGroupCheckIsDisabledAndUserIsNotLoggedIn(): void
    {
        /** @var ExtensionConfiguration&\PHPUnit\Framework\MockObject\MockObject $extensionConfiguration */
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfiguration->method('isEnableGroupCheck')->willReturn(false);
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user = new FrontendUserAuthentication();
        $user->user['uid'] = 0;
        $context->method('getAspect')->willReturn(new UserAspect($user));

        $tokenRefreshMiddleware = new TokenRefreshMiddleware($extensionConfiguration, $context);
        $request = new ServerRequest('https://example.org', 'GET');
        $expected = $this->requestHandler->handle($request);

        $response = $tokenRefreshMiddleware->process($request, $this->requestHandler);

        self::assertSame(200, $response->getStatusCode());
        self::assertEquals($expected->getBody()->getContents(), $response->getBody()->getContents());
    }

    public function testProcessShouldNotRefreshTokenWhenGroupCheckIsDisabledAndUserIsSame()
    {
        /** @var ExtensionConfiguration&\PHPUnit\Framework\MockObject\MockObject $extensionConfiguration */
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfiguration->method('isEnableGroupCheck')->willReturn(false);
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user = new FrontendUserAuthentication();
        $user->user['uid'] = 456;
        $context->method('getAspect')->willReturn(new UserAspect($user));

        $tokenRefreshMiddleware = new TokenRefreshMiddleware($extensionConfiguration, $context);
        $request = new ServerRequest('https://example.org', 'GET');
        $expected = $this->requestHandler->handle($request);

        $response = $tokenRefreshMiddleware->process($request, $this->requestHandler);

        $body = $response->getBody();
        $body->rewind();

        self::assertSame(200, $response->getStatusCode());
        self::assertEquals($expected->getBody()->getContents(), $body->getContents());
    }

    public function testProcessShouldRefreshTokenWhenGroupCheckIsDisabledAndUserIsDifferent()
    {
        /** @var ExtensionConfiguration&\PHPUnit\Framework\MockObject\MockObject $extensionConfiguration */
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfiguration->method('isEnableGroupCheck')->willReturn(false);
        $extensionConfiguration->method('getDocumentRootPath')->willReturn('/');
        $extensionConfiguration->method('getLinkPrefix')->willReturn('securedl');
        $extensionConfiguration->method('getTokenPrefix')->willReturn('sdl-');
        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $user = new FrontendUserAuthentication();
        $user->user['uid'] = 356;
        $context->method('getAspect')->willReturn(new UserAspect($user));

        $tokenRefreshMiddleware = new TokenRefreshMiddleware($extensionConfiguration, $context);
        $request = new ServerRequest('https://example.org', 'GET');
        $expected = $this->createRequestHandler(356)->handle($request);

        $response = $tokenRefreshMiddleware->process($request, $this->createRequestHandler(456));

        $body = $response->getBody();
        $body->rewind();

        self::assertSame(200, $response->getStatusCode());
        self::assertEquals($expected->getBody()->getContents(), $body->getContents());
    }

    protected function createRequestHandler(int $userId): RequestHandlerInterface
    {
        $GLOBALS['userId'] = $userId;

        return new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $token = JWT::encode(
                    ['user' => $GLOBALS['userId']],
                    $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'],
                    'HS256'
                );

                return new HtmlResponse(
                    '<a href="/securedl/sdl-' . $token . '">Download</a>'
                );
            }
        };
    }

    public function testProcessShouldRefreshTokenWhenGroupCheckIsDisabled(): void
    {
        /** @var ExtensionConfiguration&\PHPUnit\Framework\MockObject\MockObject $extensionConfiguration */
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $extensionConfiguration->method('isEnableGroupCheck')->willReturn(false);

        $tokenRefreshMiddleware = new TokenRefreshMiddleware($extensionConfiguration);
        $request = new ServerRequest('https://example.org', 'GET');
        $expected = $this->requestHandler->handle($request);

        $response = $tokenRefreshMiddleware->process($request, $this->requestHandler);

        self::assertSame(200, $response->getStatusCode());
        self::assertEquals($expected->getBody()->getContents(), $response->getBody()->getContents());
    }

}
