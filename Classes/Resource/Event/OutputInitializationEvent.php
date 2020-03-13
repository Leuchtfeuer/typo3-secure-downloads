<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Resource\Event;

final class OutputInitializationEvent
{
    private $userId;

    private $userGroups;

    private $file;

    private $expiryTime;

    public function __construct(int $userId, string $userGroups, string $file, int $expiryTime)
    {
        $this->userId = $userId;
        $this->userGroups = $userGroups;
        $this->file = $file;
        $this->expiryTime = $expiryTime;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): void
    {
        $this->userId = $userId;
    }

    public function getUserGroups(): string
    {
        return $this->userGroups;
    }

    public function setUserGroups(string $userGroups): void
    {
        $this->userGroups = $userGroups;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function setFile(string $file): void
    {
        $this->file = $file;
    }

    public function getExpiryTime(): int
    {
        return $this->expiryTime;
    }

    public function setExpiryTime(int $expiryTime): void
    {
        $this->expiryTime = $expiryTime;
    }
}
