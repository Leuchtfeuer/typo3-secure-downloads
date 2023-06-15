<?php

declare(strict_types=1);
namespace Leuchtfeuer\SecureDownloads\Middleware;

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

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * PSR-15 middleware for delivering secured files to the browser.
 */
class TokenRefreshMiddleware implements MiddlewareInterface
{
    /**
     * @var string The URL schema before JWT
     */
    protected $assetPrefix;

    /**
     * @var bool is group check enabled
     */
    protected $isEnableGroupCheck;

    /**
     * @var Context
     */
    private $context;

    public function __construct(?ExtensionConfiguration $extensionConfiguration = null, ?Context $context = null)
    {
        $this->setExtensionConfiguration(
            $extensionConfiguration ?? GeneralUtility::makeInstance(ExtensionConfiguration::class)
        );

        $this->context = $context ?? GeneralUtility::makeInstance(Context::class);
    }

    public function setExtensionConfiguration(ExtensionConfiguration $extensionConfiguration): void
    {
        $this->assetPrefix = sprintf(
            '%s%s/%s',
            $extensionConfiguration->getDocumentRootPath(),
            $extensionConfiguration->getLinkPrefix(),
            $extensionConfiguration->getTokenPrefix()
        );

        $this->isEnableGroupCheck = $extensionConfiguration->isEnableGroupCheck();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->isEnableGroupCheck) {
            $currentUser = $this->context->getAspect('frontend.user');
            $currentUserId = (int)$currentUser->get('id');

            if ($currentUserId) {
                $response = $handler->handle($request);

                $body = $response->getBody();
                $body->rewind();
                $content = $body->getContents();

                $foundJwtTokens = [];

                $pattern = '/' . preg_quote($this->assetPrefix, '/') . '([a-zA-Z0-9_.\-]+)/';
                $replaces = [];

                if (preg_match_all($pattern, $content, $foundJwtTokens)) {
                    foreach ($foundJwtTokens[1] as $foundJwtToken) {
                        try {
                            $data = JWT::decode($foundJwtToken, new Key($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'], 'HS256'));
                            if ((int)$data->user !== $currentUserId) {
                                $data->user = $currentUserId;
                                $newToken = JWT::encode($data, $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'], 'HS256');
                                $replaces[$foundJwtToken] = $newToken;
                            }
                        } catch (\Exception $exception) {
                            // Do nothing
                        }
                    }
                    if (count($replaces)) {
                        foreach ($replaces as $search => $replace) {
                            $content = str_replace($search, $replace, $content);
                        }
                    }
                }

                $body = new Stream('php://temp', 'rw');
                $body->write($content);
                return $response->withBody($body);
            }
        }

        return $handler->handle($request);
    }
}
