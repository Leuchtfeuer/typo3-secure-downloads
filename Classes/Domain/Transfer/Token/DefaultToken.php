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

namespace Leuchtfeuer\SecureDownloads\Domain\Transfer\Token;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Leuchtfeuer\SecureDownloads\Domain\Repository\LogRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DefaultToken extends AbstractToken
{
    protected function getKey(): string
    {
        return $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
    }

    protected function getAlgorithm(): string
    {
        return 'HS256';
    }

    /**
     * @param array<mixed>|null $payload
     */
    public function encode(?array $payload = null): string
    {
        return JWT::encode($payload ?? $this->getPayload(), $this->getKey(), $this->getAlgorithm());
    }

    public function decode(string $jsonWebToken): void
    {
        $data = (array)JWT::decode($jsonWebToken, new Key($this->getKey(), $this->getAlgorithm()));

        foreach ($data as $property => $value) {
            if (property_exists(self::class, $property)) {
                $this->$property = $value;
            }
        }
    }

    /**
     * @param array<string, mixed> $parameters
     * @throws AspectNotFoundException
     * @throws ResourceDoesNotExistException
     */
    public function log(array $parameters = []): void
    {
        if ($this->logged === false) {
            $logRepository = GeneralUtility::makeInstance(LogRepository::class);
            $logRepository->logDownload(
                $this,
                $parameters['fileSize'],
                $parameters['mimeType'],
                GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.user', 'id')
            );
            $this->logged = true;
        }
    }
}
