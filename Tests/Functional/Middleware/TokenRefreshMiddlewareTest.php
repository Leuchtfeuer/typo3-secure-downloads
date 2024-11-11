<?php

declare(strict_types=1);

namespace Leuchtfeuer\SecureDownloads\Tests\Functional\Middleware;

use PHPUnit\Framework\TestCase;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\SecureDownloads\Middleware\TokenRefreshMiddleware;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use Firebase\JWT\JWT;
use TYPO3\CMS\Core\Http\HtmlResponse;

class TokenRefreshMiddlewareTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/secure_downloads'
    ];

    protected string $assetPrefix;
    protected RequestHandlerInterface $requestHandler;
    protected Context $context;

    protected function setUp(): void
    {
        parent::setUp();

        $tokenSecret = '727fdcee031b34f1bdf45ae5fda62b032f4cfb61f8f0a4313443d55948ba9654fc193b827381576abc7a87dc7ac2c247';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = $tokenSecret;

        $this->requestHandler = new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $token = JWT::encode(
                    ['user' => 456],
                    $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'],
                    'HS256'
                );

                return new HtmlResponse(
                    '<a href="/securedl/sdl-' . $token . '">Download</a>'
                );
            }
        };

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

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($expected->getBody()->getContents(), $response->getBody()->getContents());
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

       //TODO  $context->expects()
        $user = new FrontendUserAuthentication();
        $user->user['uid'] = 0;
        $context->method('getAspect')->willReturn(new UserAspect($user));

        $tokenRefreshMiddleware = new TokenRefreshMiddleware($extensionConfiguration, $context);
        $request = new ServerRequest('https://example.org', 'GET');
        $expected = $this->requestHandler->handle($request);

        $response = $tokenRefreshMiddleware->process($request, $this->requestHandler);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($expected->getBody()->getContents(), $response->getBody()->getContents());
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

        $this->assertSame(200, $response->getStatusCode());
        $this->assertEquals($expected->getBody()->getContents(), $response->getBody()->getContents());
    }

    public function ProcessShouldRefreshTokensWhenUserIsLoggedIn(): void
    {
        $tokenSecret = '727fdcee031b34f1bdf45ae5fda62b032f4cfb61f8f0a4313443d55948ba9654fc193b827381576abc7a87dc7ac2c247';
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = $tokenSecret;
        
        $this->requestHandler = new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $token = JWT::encode(
                    ['user' => 456],
                    $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'],
                    'HS256'
                );
                
                return new HtmlResponse(
                    '<a href="/securedl/sdl-' . $token . '">Download</a>'
                );
            }
        };

        /** @var ExtensionConfiguration&\PHPUnit\Framework\MockObject\MockObject $extensionConfiguration */
        $extensionConfiguration = $this->getMockBuilder(ExtensionConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $context = $this->get(Context::class);
        $user = new FrontendUserAuthentication();
        $user->user['uid'] = 456; 
        $context->setAspect('frontend.user', new UserAspect($user));
        
        $tokenRefreshMiddleware = new TokenRefreshMiddleware($extensionConfiguration, $context);
        $request = new ServerRequest('https://example.org', 'GET');

        $response = $tokenRefreshMiddleware->process($request, $this->requestHandler);
        
        // Assert basic response properties
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(
            'text/html; charset=utf-8',
            $response->getHeader('Content-Type')[0]
        );
        
        $responseBody = json_decode($response->getBody()->getContents(), true);
    }
} 