<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Resource\Event;

final class AfterFileRetrievedEvent
{
    private $file;

    private $fileName;

    public function __construct(string $file, string $fileName)
    {
        $this->file = $file;
        $this->fileName = $fileName;
    }

    public function getFile(): string
    {
        return $this->file;
    }

    public function setFile(string $file): void
    {
        $this->file = $file;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }
}
