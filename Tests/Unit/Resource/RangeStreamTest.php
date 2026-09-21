<?php

/*
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\SecureDownloads\Tests\Unit\Resource;

use Leuchtfeuer\SecureDownloads\Resource\RangeStream;
use PHPUnit\Framework\TestCase;

class RangeStreamTest extends TestCase
{
    private string $filePath;

    private string $content;

    protected function setUp(): void
    {
        parent::setUp();

        $this->content = implode('', array_map(static fn(int $i): string => chr($i % 256), range(0, 19999)));
        $this->filePath = tempnam(sys_get_temp_dir(), 'rangestream_');
        file_put_contents($this->filePath, $this->content);
    }

    protected function tearDown(): void
    {
        @unlink($this->filePath);
        parent::tearDown();
    }

    public function testGetContentsReturnsOnlyTheRequestedRange(): void
    {
        $stream = new RangeStream($this->filePath, 1000, 2000);

        self::assertSame(2000, $stream->getSize());
        self::assertSame(substr($this->content, 1000, 2000), $stream->getContents());
        self::assertTrue($stream->eof());
    }

    public function testEmitOutputsOnlyTheRequestedRangeInChunks(): void
    {
        $stream = new RangeStream($this->filePath, 500, 20000);

        ob_start();
        $stream->emit();
        $output = ob_get_clean();

        self::assertSame(substr($this->content, 500, 20000), $output);
    }

    public function testReadAdvancesPositionWithinTheRange(): void
    {
        $stream = new RangeStream($this->filePath, 10, 30);

        $first = $stream->read(10);
        $second = $stream->read(100);

        self::assertSame(substr($this->content, 10, 10), $first);
        self::assertSame(substr($this->content, 20, 20), $second);
        self::assertTrue($stream->eof());
        self::assertSame('', $stream->read(10));
    }

    public function testSeekIsRelativeToTheRangeNotTheFile(): void
    {
        $stream = new RangeStream($this->filePath, 100, 50);

        $stream->seek(10);

        self::assertSame(10, $stream->tell());
        self::assertSame(substr($this->content, 110, 5), $stream->read(5));
    }
}
