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

abstract class AbstractToken
{
    protected int $iat = 0;
    protected int $exp = 0;
    protected int $user = 0;

    /**
     * @var array<int>
     */
    protected $groups = [];
    protected string $file = '';
    protected int $page = 0;
    protected string $implementationClassName = __CLASS__;
    protected bool $logged = false;

    public function __construct()
    {
        $this->iat = time();
        $this->implementationClassName = static::class;
    }

    /**
     * @param array<mixed>|null $payload
     * @return string
     */
    abstract public function encode(?array $payload = null): string;

    abstract public function decode(string $jsonWebToken): void;

    /**
     * @param array<string, mixed> $parameters
     */
    abstract public function log(array $parameters = []): void;

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

    /**
     * @return int[]
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @param int[] $groups
     */
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

    /**
     * @return array<string, mixed>
     */
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

    public function getHash(): string
    {
        return md5($this->getUser() . implode('', $this->getGroups()) . $this->getFile() . $this->getPage());
    }
}
