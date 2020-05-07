<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Domain\Transfer\Token;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Firebase\JWT\JWT;
use Leuchtfeuer\SecureDownloads\Domain\Model\Log;
use Leuchtfeuer\SecureDownloads\Domain\Repository\LogRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class DefaultToken extends AbstractToken
{
    protected $logged = false;

    protected function getKey(): string
    {
        return $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'];
    }

    protected function getAlgorithm(): string
    {
        return 'HS256';
    }

    public function encode(?array $payload = null): string
    {
        return JWT::encode($payload ?? $this->getPayload(), $this->getKey(), $this->getAlgorithm());
    }

    public function decode(string $jsonWebToken): void
    {
        $data = (array)JWT::decode($jsonWebToken, $this->getKey(), [$this->getAlgorithm()]);

        foreach ($data ?? [] as $property => $value) {
            if (property_exists(__CLASS__, $property)) {
                $this->$property = $value;
            }
        }
    }

    public function getHash(): string
    {
        return md5($this->getUser() . $this->getGroups() . $this->getFile() . $this->getPage());
    }

    public function log(array $parameters = []): void
    {
        if ($this->logged === false) {
            $logRepository = GeneralUtility::makeInstance(LogRepository::class);
            $logRepository->logDownload($this, $parameters['fileSize'], $parameters['mimeType'], $parameters['user']);
            $this->logged = true;
        }
    }

    public function getPayload(): array
    {
        return [
            'iat' => $this->getIat(),
            'exp' => $this->getExp(),
            'user' => $this->getUser(),
            'groups' => $this->getGroups(),
            'file' => $this->getFile(),
            'page' => $this->getPage(),
        ];
    }
}
