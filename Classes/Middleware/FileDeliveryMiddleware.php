<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\Middleware;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

use Bitmotion\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Bitmotion\SecureDownloads\Resource\FileDelivery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileDeliveryMiddleware implements MiddlewareInterface
{
    protected $assetPrefix;

    public function __construct()
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $this->assetPrefix = sprintf('/%s/%s', $extensionConfiguration->getLinkPrefix(), $extensionConfiguration->getTokenPrefix());
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->isResponsible($request)) {
            $jwt = mb_substr($request->getUri()->getPath(), mb_strlen($this->assetPrefix));
            GeneralUtility::makeInstance(FileDelivery::class, $jwt)->deliver();
        }

        return $handler->handle($request);
    }

    public function isResponsible(ServerRequestInterface $request)
    {
        return mb_strpos($request->getUri()->getPath(), $this->assetPrefix) === 0 && $request->getMethod() === 'GET';
    }
}
