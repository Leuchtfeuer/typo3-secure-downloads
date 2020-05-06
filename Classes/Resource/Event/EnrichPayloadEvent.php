<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Resource\Event;

use Leuchtfeuer\SecureDownloads\Domain\Transfer\Download;

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

final class EnrichPayloadEvent
{
    private $payload;

    private $download;

    public function __construct(array $payload, Download $download)
    {
        $this->payload = $payload;
        $this->download = $download;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): void
    {
        $this->payload = $payload;
    }

    public function getDownload(): Download
    {
        return $this->download;
    }
}
