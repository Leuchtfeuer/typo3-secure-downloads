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

use Leuchtfeuer\SecureDownloads\Resource\RangeRequest;
use PHPUnit\Framework\TestCase;

class RangeRequestTest extends TestCase
{
    public function testNoRangeHeaderServesFullFile(): void
    {
        $rangeRequest = RangeRequest::fromHeader('', 1000);

        self::assertFalse($rangeRequest->isRequested());
        self::assertTrue($rangeRequest->isSatisfiable());
        self::assertSame(0, $rangeRequest->getStart());
        self::assertSame(999, $rangeRequest->getEnd());
        self::assertSame(1000, $rangeRequest->getLength());
    }

    public function testSimpleByteRange(): void
    {
        $rangeRequest = RangeRequest::fromHeader('bytes=100-199', 1000);

        self::assertTrue($rangeRequest->isRequested());
        self::assertTrue($rangeRequest->isSatisfiable());
        self::assertSame(100, $rangeRequest->getStart());
        self::assertSame(199, $rangeRequest->getEnd());
        self::assertSame(100, $rangeRequest->getLength());
        self::assertSame('bytes 100-199/1000', $rangeRequest->getContentRange());
    }

    public function testOpenEndedRangeIsClampedToFileSize(): void
    {
        $rangeRequest = RangeRequest::fromHeader('bytes=900-', 1000);

        self::assertTrue($rangeRequest->isRequested());
        self::assertTrue($rangeRequest->isSatisfiable());
        self::assertSame(900, $rangeRequest->getStart());
        self::assertSame(999, $rangeRequest->getEnd());
        self::assertSame(100, $rangeRequest->getLength());
    }

    public function testRangeEndBeyondFileSizeIsClamped(): void
    {
        $rangeRequest = RangeRequest::fromHeader('bytes=900-999999', 1000);

        self::assertTrue($rangeRequest->isSatisfiable());
        self::assertSame(900, $rangeRequest->getStart());
        self::assertSame(999, $rangeRequest->getEnd());
    }

    public function testSuffixRangeReturnsLastBytes(): void
    {
        $rangeRequest = RangeRequest::fromHeader('bytes=-500', 1000);

        self::assertTrue($rangeRequest->isRequested());
        self::assertTrue($rangeRequest->isSatisfiable());
        self::assertSame(500, $rangeRequest->getStart());
        self::assertSame(999, $rangeRequest->getEnd());
        self::assertSame(500, $rangeRequest->getLength());
    }

    public function testSuffixRangeLargerThanFileReturnsWholeFile(): void
    {
        $rangeRequest = RangeRequest::fromHeader('bytes=-5000', 1000);

        self::assertSame(0, $rangeRequest->getStart());
        self::assertSame(999, $rangeRequest->getEnd());
        self::assertSame(1000, $rangeRequest->getLength());
    }

    public function testRangeStartBeyondFileSizeIsUnsatisfiable(): void
    {
        $rangeRequest = RangeRequest::fromHeader('bytes=5000-', 1000);

        self::assertTrue($rangeRequest->isRequested());
        self::assertFalse($rangeRequest->isSatisfiable());
        self::assertSame('bytes */1000', $rangeRequest->getUnsatisfiableContentRange());
    }

    public function testMalformedRangeHeaderIsIgnored(): void
    {
        $rangeRequest = RangeRequest::fromHeader('not-a-range', 1000);

        self::assertFalse($rangeRequest->isRequested());
        self::assertTrue($rangeRequest->isSatisfiable());
        self::assertSame(1000, $rangeRequest->getLength());
    }

    public function testMultipleRangesAreNotSupportedAndIgnored(): void
    {
        $rangeRequest = RangeRequest::fromHeader('bytes=0-10,20-30', 1000);

        self::assertFalse($rangeRequest->isRequested());
        self::assertTrue($rangeRequest->isSatisfiable());
    }
}
