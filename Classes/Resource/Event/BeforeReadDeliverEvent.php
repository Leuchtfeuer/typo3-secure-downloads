<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Resource\Event;

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

final class BeforeReadDeliverEvent
{
    private $outputFunction;

    private $header;

    private $fileName;

    private $mimeType;

    private $forceDownload;

    public function __construct(string $outputFunction, array $header, string $fileName, string $mimeType, bool $forceDownload)
    {
        $this->outputFunction = $outputFunction;
        $this->header = $header;
        $this->fileName = $fileName;
        $this->mimeType = $mimeType;
        $this->forceDownload = $forceDownload;
    }

    public function getOutputFunction(): string
    {
        return $this->outputFunction;
    }

    public function setOutputFunction(string $outputFunction): void
    {
        $this->outputFunction = $outputFunction;
    }

    public function getHeader(): array
    {
        return $this->header;
    }

    public function setHeader(array $header): void
    {
        $this->header = $header;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function getMimeType(): string
    {
        return $this->mimeType;
    }

    public function isForceDownload(): bool
    {
        return $this->forceDownload;
    }
}
