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

namespace Leuchtfeuer\SecureDownloads\Resource;

/**
 * Parses and validates a single HTTP byte range (RFC 7233) from a "Range" request header
 * against a known file size. Only a single range is supported, which covers common HTML5
 * video/audio seeking use cases.
 */
class RangeRequest
{
    private function __construct(
        private readonly bool $requested,
        private readonly bool $satisfiable,
        private readonly int $start,
        private readonly int $end,
        private readonly int $fileSize
    ) {}

    public static function fromHeader(string $rangeHeader, int $fileSize): self
    {
        $lastBytePosition = max($fileSize - 1, 0);

        if ($rangeHeader === '' || $fileSize <= 0) {
            return new self(false, true, 0, $lastBytePosition, $fileSize);
        }

        // Multiple ranges (e.g. "bytes=0-10,20-30") are not supported and are treated as if no range was requested.
        if (!preg_match('/^bytes=(\d*)-(\d*)$/', trim($rangeHeader), $matches) || ($matches[1] === '' && $matches[2] === '')) {
            return new self(false, true, 0, $lastBytePosition, $fileSize);
        }

        if ($matches[1] === '') {
            // Suffix range, e.g. "bytes=-500" means the last 500 bytes of the file.
            $start = max($fileSize - (int)$matches[2], 0);
            $end = $lastBytePosition;
        } else {
            $start = (int)$matches[1];
            $end = $matches[2] === '' ? $lastBytePosition : min((int)$matches[2], $lastBytePosition);
        }

        if ($start > $lastBytePosition || $start > $end) {
            return new self(true, false, 0, $lastBytePosition, $fileSize);
        }

        return new self(true, true, $start, $end, $fileSize);
    }

    /**
     * True when the client sent a satisfiable "Range" header, false when no range was requested
     * (or the header could not be parsed, in which case the full file should be served).
     */
    public function isRequested(): bool
    {
        return $this->requested;
    }

    /**
     * False when a range was requested but is outside the bounds of the file, in which case
     * a 416 Range Not Satisfiable response should be returned instead.
     */
    public function isSatisfiable(): bool
    {
        return $this->satisfiable;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    public function getEnd(): int
    {
        return $this->end;
    }

    public function getLength(): int
    {
        return $this->end - $this->start + 1;
    }

    public function getContentRange(): string
    {
        return sprintf('bytes %d-%d/%d', $this->start, $this->end, $this->fileSize);
    }

    public function getUnsatisfiableContentRange(): string
    {
        return sprintf('bytes */%d', $this->fileSize);
    }
}
