<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\Domain\Model;

/***************************************************************
 *
 *  Copyright notice
 *
 *  (c) 2016 Bitmotion GmbH (typo3-ext@bitmotion.de)
 *
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Log extends AbstractEntity
{
    /**
     * @var string
     */
    protected $fileId = '';

    /**
     * @var string
     */
    protected $fileName = '';

    /**
     * @var string
     */
    protected $filePath = '';

    /**
     * @var int
     */
    protected $fileSize = 0;

    /**
     * @var string
     */
    protected $fileType = '';

    /**
     * @var string
     */
    protected $mediaType = '';

    /**
     * @var int
     * @deprecated Since 3.0, will be removed in 4.0
     */
    protected $bytesDownloaded = 0;

    /**
     * @var string
     */
    protected $protected = '';

    /**
     * @var string
     */
    protected $host = '';

    /**
     * @deprecated Since 3.0, will be removed in 4.0
     */
    protected $typo3Mode = '';

    /**
     * @var int
     */
    protected $user;

    /**
     * @var int
     */
    protected $page = 0;

    /**
     * @var int
     */
    protected $tstamp = 0;

    public function getTstamp(): int
    {
        return $this->tstamp;
    }

    public function setTstamp(int $tstamp)
    {
        $this->tstamp = $tstamp;
    }

    /**
     * @return array|null
     */
    public function getUserObject()
    {
        if ($this->user !== null && $this->user !== 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');

            return $queryBuilder
                ->select('*')
                ->from('fe_users')
                ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($this->user, \PDO::PARAM_INT)))
                ->execute()
                ->fetch();
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'file_id' => $this->getFileId(),
            'file_name' => $this->getFileName(),
            'file_path' => $this->getFilePath(),
            'file_size' => $this->getFileSize(),
            'file_type' => $this->getFileType(),
            'media_type' => $this->getMediaType(),
            'bytes_downloaded' => $this->getBytesDownloaded(),
            'protected' => $this->getProtected(),
            'host' => $this->getHost(),
            'typo3_mode' => $this->getTypo3Mode(),
            'user' => $this->getUser(),
            'page' => $this->getPage(),
            'tstamp' => time(),
            'crdate' => time(),
        ];
    }

    public function getFileId(): string
    {
        return $this->fileId;
    }

    public function setFileId(string $fileId)
    {
        $this->fileId = $fileId;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName)
    {
        $this->fileName = $fileName;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath)
    {
        $this->filePath = $filePath;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize)
    {
        $this->fileSize = $fileSize;
    }

    public function getFileType(): string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType)
    {
        $this->fileType = $fileType;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function setMediaType(string $mediaType)
    {
        $this->mediaType = $mediaType;
    }

    /**
     * @deprecated Since 3.0, will be removed in 4.0
     */
    public function getBytesDownloaded(): int
    {
        return $this->bytesDownloaded;
    }

    /**
     * @deprecated Since 3.0, will be removed in 4.0
     */
    public function setBytesDownloaded(int $bytesDownloaded)
    {
        $this->bytesDownloaded = $bytesDownloaded;
    }

    public function getProtected(): string
    {
        return $this->protected;
    }

    public function setProtected(string $protected)
    {
        $this->protected = $protected;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host)
    {
        $this->host = $host;
    }

    /**
     * @deprecated Since 3.0, will be removed in 4.0
     */
    public function getTypo3Mode(): string
    {
        return $this->typo3Mode;
    }

    /**
     * @deprecated Since 3.0, will be removed in 4.0
     */
    public function setTypo3Mode(string $typo3Mode)
    {
        $this->typo3Mode = $typo3Mode;
    }

    /**
     * @return int $user
     */
    public function getUser(): int
    {
        return (int)$this->user;
    }

    /**
     * @return void
     */
    public function setUser(int $user)
    {
        $this->user = $user;
    }

    /**
     * @return int $page
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * @return void
     */
    public function setPage(int $page)
    {
        $this->page = $page;
    }
}
