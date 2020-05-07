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

abstract class AbstractToken
{
    /**
     * @var int
     */
    private $iat = 0;

    /**
     * @var int
     */
    private $exp = 0;

    /**
     * @var int
     */
    private $user = 0;

    /**
     * @var array
     */
    private $groups = [];

    /**
     * @var string
     */
    private $file = '';

    /**
     * @var int
     */
    private $page = 0;

    protected $implementationClassName = __CLASS__;

    protected $logged = false;

    public function __construct()
    {
        $this->iat = time();
        $this->implementationClassName = get_called_class();
    }

    abstract public function encode(?array $payload = null): string;

    abstract public function decode(string $jsonWebToken): void;

    abstract public function log(array $parameters = []): void;

    abstract public function getHash(): string;

    public function getIat(): int
    {
        return $this->iat;
    }

    public function getExp(): int
    {
        return $this->exp;
    }

    public function setExp(int $exp): void
    {
        $this->exp = $exp;
    }

    public function getUser(): int
    {
        return $this->user;
    }

    public function setUser(int $user): void
    {
        $this->user = $user;
    }

    public function getGroups(): array
    {
        return $this->groups;
    }

    public function setGroups(array $groups): void
    {
        $this->groups = $groups;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function setFile(string $file): void
    {
        $this->file = $file;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
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
