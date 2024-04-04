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

namespace Leuchtfeuer\SecureDownloads\Middleware;

use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\SecureDownloads\Resource\FileDelivery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * PSR-15 middleware for delivering secured files to the browser.
 */
class FileDeliveryMiddleware implements MiddlewareInterface
{
    /**
     * @var string The URL schema before JWT
     */
    protected $assetPrefix;

    public function __construct()
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);

        $this->assetPrefix = sprintf(
            '%s%s/%s',
            $extensionConfiguration->getDocumentRootPath(),
            $extensionConfiguration->getLinkPrefix(),
            $extensionConfiguration->getTokenPrefix()
        );
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isResponsible($request)) {
            $frontendUserAuthentication = $request->getAttribute('frontend.user');
            $frontendUserAuthentication->fetchGroupData($request);

            $cleanPath = mb_substr(urldecode($request->getUri()->getPath()), mb_strlen($this->assetPrefix));
            [$jwt, $basePath] = explode('/', $cleanPath);

            return GeneralUtility::makeInstance(FileDelivery::class)->deliver($jwt, $request);
        }

        return $handler->handle($request);
    }

    /**
     * @param ServerRequestInterface $request The request interface
     *
     * @return bool Returns true when the Secure Downloads middleware is responsible for handling the actual request.
     */
    public function isResponsible(ServerRequestInterface $request)
    {
        return mb_strpos(urldecode($request->getUri()->getPath()), $this->assetPrefix) === 0 && $request->getMethod() === 'GET';
    }
}
