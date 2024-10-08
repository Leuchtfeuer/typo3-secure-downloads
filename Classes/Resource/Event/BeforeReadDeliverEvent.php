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

namespace Leuchtfeuer\SecureDownloads\Resource\Event;

/**
 * This event is executed just before the file is sent to the browser. It is the last chance to influence both the output
 * function and the headers sent.
 */
final class BeforeReadDeliverEvent
{
    /**
     * @param string $outputFunction Contains the output function as string. This property is deprecated and will be removed in
     *                               further releases since the output function can only be one of "x-accel-redirect" or "stream".
     * @param string[]  $header         An array of header which will be sent to the browser. You can add your own headers or remove
     *                               default ones.
     * @param string $fileName       The name of the file. This property is read-only.
     * @param string $mimeType       The mime type of the file. This property is read-only.
     * @param bool   $forceDownload  Information whether the file should be forced to download or not. This property is read-only.
     */
    public function __construct(private string $outputFunction, private array $header, private readonly string $fileName, private readonly string $mimeType, private readonly bool $forceDownload) {}

    public function getOutputFunction(): string
    {
        return $this->outputFunction;
    }

    public function setOutputFunction(string $outputFunction): void
    {
        $this->outputFunction = $outputFunction;
    }

    /**
     * @return string[]
     */
    public function getHeader(): array
    {
        return $this->header;
    }

    /**
     * @param string[] $header
     */
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
