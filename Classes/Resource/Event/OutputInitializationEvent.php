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
