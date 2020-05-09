<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Middleware;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Bitmotion\SecureDownloads\Resource\FileDelivery;
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
            // TODO: Remove the $GLOBALS array when dropping TYPO3 9 LTS support
            $frontendUserAuthentication = $request->getAttribute('frontend.user') ?? $GLOBALS['TSFE']->fe_user;
            $frontendUserAuthentication->fetchGroupData();

            $cleanPath = mb_substr(urldecode($request->getUri()->getPath()), mb_strlen($this->assetPrefix));
            [$jwt, $basePath] = explode('/', $cleanPath);
            return (new FileDelivery($jwt))->deliver($request);
        }

        return $handler->handle($request);
    }

    public function isResponsible(ServerRequestInterface $request)
    {
        return mb_strpos(urldecode($request->getUri()->getPath()), $this->assetPrefix) === 0 && $request->getMethod() === 'GET';
    }
}
