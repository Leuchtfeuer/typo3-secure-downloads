<?php

declare(strict_types=1);
namespace Leuchtfeuer\SecureDownloads\Resource\Event;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

/**
 * This event is executed after the access checks has been performed and both the file and the file name have been read from the
 * token. Afterwards, the check is made whether the file is available on the file system.
 */
final class AfterFileRetrievedEvent
{
    /**
     * @var string
     */
    private $file;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @param string $file     Contains the absolute path to the file on the file system. You can change this property.
     * @param string $fileName Contains the name of the file. You can change this so that another file name is used when
     *                         downloading this file.
     */
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
