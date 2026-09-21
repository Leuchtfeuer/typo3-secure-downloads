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

use TYPO3\CMS\Core\Http\SelfEmittableStreamInterface;

/**
 * A PSR-7 stream representing a single byte range of a file on disk. Implements
 * SelfEmittableStreamInterface so TYPO3 can output the requested range in fixed-size
 * chunks directly, without ever loading the full range (or the full file) into memory.
 */
class RangeStream implements SelfEmittableStreamInterface
{
    private const CHUNK_SIZE = 8192;

    /**
     * @var resource
     */
    private $resource;

    private int $position = 0;

    public function __construct(
        private readonly string $filePath,
        private readonly int $start,
        private readonly int $length
    ) {
        $resource = fopen($this->filePath, 'rb');

        if ($resource === false) {
            throw new \RuntimeException(sprintf('Unable to open file "%s" for reading.', $filePath), 1755000001);
        }

        $this->resource = $resource;
        fseek($this->resource, $this->start);
    }

    /**
     * Outputs the requested byte range directly to the output buffer in fixed-size chunks.
     */
    public function emit(): void
    {
        fseek($this->resource, $this->start);
        $bytesLeft = $this->length;

        while ($bytesLeft > 0 && !feof($this->resource)) {
            $chunk = fread($this->resource, min(self::CHUNK_SIZE, $bytesLeft));

            if ($chunk === false || $chunk === '') {
                break;
            }

            echo $chunk;
            flush();
            $bytesLeft -= strlen($chunk);
        }
    }

    public function __toString(): string
    {
        try {
            $this->rewind();

            return $this->getContents();
        } catch (\Throwable) {
            return '';
        }
    }

    public function close(): void
    {
        if (is_resource($this->resource)) {
            fclose($this->resource);
        }
    }

    public function detach()
    {
        $resource = $this->resource;
        unset($this->resource);

        return $resource;
    }

    public function getSize(): ?int
    {
        return $this->length;
    }

    public function tell(): int
    {
        return $this->position;
    }

    public function eof(): bool
    {
        return $this->position >= $this->length;
    }

    public function isSeekable(): bool
    {
        return true;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        $target = match ($whence) {
            SEEK_SET => $offset,
            SEEK_CUR => $this->position + $offset,
            SEEK_END => $this->length + $offset,
            default => throw new \InvalidArgumentException('Invalid whence.', 1755000002),
        };

        $target = max(0, min($target, $this->length));
        fseek($this->resource, $this->start + $target);
        $this->position = $target;
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return false;
    }

    public function write($string): int
    {
        throw new \RuntimeException('Cannot write to a ' . self::class, 1755000003);
    }

    public function isReadable(): bool
    {
        return true;
    }

    public function read($length): string
    {
        $remaining = $this->length - $this->position;

        if ($remaining <= 0) {
            return '';
        }

        $data = (string)fread($this->resource, min($length, $remaining));
        $this->position += strlen($data);

        return $data;
    }

    public function getContents(): string
    {
        $contents = '';

        while (!$this->eof()) {
            $contents .= $this->read(self::CHUNK_SIZE);
        }

        return $contents;
    }

    public function getMetadata($key = null)
    {
        $metadata = stream_get_meta_data($this->resource);

        return $key === null ? $metadata : ($metadata[$key] ?? null);
    }
}
