<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Resource\Event;

use Leuchtfeuer\SecureDownloads\Domain\Transfer\Download;

final class OutputInitializationEvent
{
    private $download;

    public function __construct(Download $download)
    {
        $this->download = $download;
    }

    public function getDownload(): Download
    {
        return $this->download;
    }

    public function setDownload(Download $download): void
    {
        $this->download = $download;
    }
}
