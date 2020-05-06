<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Domain\Transfer;

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

class Download
{
    protected $iat = 0;

    protected $exp = 0;

    protected $user = 0;

    protected $groups = [];

    protected $file = '';

    protected $page = 0;

    protected $logged = false;

    public function __construct(string $jsonWebToken)
    {
        $data = (array)JWT::decode($jsonWebToken, $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'], ['HS256']);

        foreach ($data as $property => $value) {
            $this->$property = $value;
        }
    }

    public function getIat(): int
    {
        return $this->iat;
    }

    public function getExp(): int
    {
        return $this->exp;
    }

    public function getUser(): int
    {
        return $this->user;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function isLogged(): bool
    {
        return $this->logged;
    }

    public function log(int $fileSize, string $mimeType, int $user)
    {
        if ($this->isLogged() === false) {
            GeneralUtility::makeInstance(LogRepository::class)->logDownload(
                $this,
                $fileSize,
                $mimeType,
                $user
            );

            $this->logged = true;
        }
    }
}
