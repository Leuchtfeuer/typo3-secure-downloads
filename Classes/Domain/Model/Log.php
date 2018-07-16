<?php
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
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

/**
 * Class Log
 * @package Bitmotion\SecureDownloads\Domain\Model
 */
class Log extends AbstractEntity
{

    /**
     * fileId
     *
     * @var string
     */
    protected $fileId = '';

    /**
     * fileName
     *
     * @var string
     */
    protected $fileName = '';

    /**
     * filePath
     *
     * @var string
     */
    protected $filePath = '';

    /**
     * fileSize
     *
     * @var int
     */
    protected $fileSize = 0;

    /**
     * fileType
     *
     * @var string
     */
    protected $fileType = '';

    /**
     * mediaType
     *
     * @var string
     */
    protected $mediaType = '';

    /**
     * bytesDownloaded
     *
     * @var int
     */
    protected $bytesDownloaded = 0;

    /**
     * protected
     *
     * @var string
     */
    protected $protected = '';

    /**
     * host
     *
     * @var string
     */
    protected $host = '';

    /**
     * typo3Mode
     *
     * @var string
     */
    protected $typo3Mode = '';

    /**
     * user
     *
     * @var int
     */
    protected $user;

    /**
     * page
     *
     * @var int
     */
    protected $page = 0;

    /**
     * timestamp
     *
     * @var int
     */
    protected $tstamp = 0;

    /**
     * Returns the tstamp
     *
     * @return int $tstamp
     */
    public function getTstamp()
    {
        return $this->tstamp;
    }

    /**
     * Sets the tstamp
     *
     * @param int $tstamp
     * @return void
     */
    public function setTstamp($tstamp)
    {
        $this->tstamp = $tstamp;
    }

    /**
     * @return array|null
     */
    public function getUserObject()
    {
        if ($this->user !== null && $this->user !== 0) {

            /** @var DeletedRestriction $deletedRestriction */
            $deletedRestriction = GeneralUtility::makeInstance(DeletedRestriction::class);

            /** @var QueryBuilder $queryBuilder */
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('fe_users');
            $queryBuilder
                ->getRestrictions()
                ->removeAll()
                ->add($deletedRestriction);

            $res = $queryBuilder
                ->select('*')
                ->from('fe_users')
                ->where($queryBuilder->expr()->eq('uid', $this->user))
                ->execute();

            return $res->fetch();
        }

        return null;
    }

    public function toArray()
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

    /**
     * Returns the fileId
     *
     * @return string $fileId
     */
    public function getFileId()
    {
        return $this->fileId;
    }

    /**
     * Sets the fileId
     *
     * @param string $fileId
     * @return void
     */
    public function setFileId($fileId)
    {
        $this->fileId = $fileId;
    }

    /**
     * Returns the fileName
     *
     * @return string $fileName
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * Sets the fileName
     *
     * @param string $fileName
     * @return void
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * Returns the filePath
     *
     * @return string $filePath
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * Sets the filePath
     *
     * @param string $filePath
     * @return void
     */
    public function setFilePath($filePath)
    {
        $this->filePath = $filePath;
    }

    /**
     * Returns the fileSize
     *
     * @return int $fileSize
     */
    public function getFileSize()
    {
        return $this->fileSize;
    }

    /**
     * Sets the fileSize
     *
     * @param int $fileSize
     * @return void
     */
    public function setFileSize($fileSize)
    {
        $this->fileSize = $fileSize;
    }

    /**
     * Returns the fileType
     *
     * @return string $fileType
     */
    public function getFileType()
    {
        return $this->fileType;
    }

    /**
     * Sets the fileType
     *
     * @param string $fileType
     * @return void
     */
    public function setFileType($fileType)
    {
        $this->fileType = $fileType;
    }

    /**
     * Returns the mediaType
     *
     * @return string $mediaType
     */
    public function getMediaType()
    {
        return $this->mediaType;
    }

    /**
     * Sets the mediaType
     *
     * @param string $mediaType
     * @return void
     */
    public function setMediaType($mediaType)
    {
        $this->mediaType = $mediaType;
    }

    /**
     * Returns the bytesDownloaded
     *
     * @return int $bytesDownloaded
     */
    public function getBytesDownloaded()
    {
        return $this->bytesDownloaded;
    }

    /**
     * Sets the bytesDownloaded
     *
     * @param int $bytesDownloaded
     * @return void
     */
    public function setBytesDownloaded($bytesDownloaded)
    {
        $this->bytesDownloaded = $bytesDownloaded;
    }

    /**
     * Returns the protected
     *
     * @return string $protected
     */
    public function getProtected()
    {
        return $this->protected;
    }

    /**
     * Sets the protected
     *
     * @param string $protected
     * @return void
     */
    public function setProtected($protected)
    {
        $this->protected = $protected;
    }

    /**
     * Returns the host
     *
     * @return string $host
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Sets the host
     *
     * @param string $host
     * @return void
     */
    public function setHost($host)
    {
        $this->host = $host;
    }

    /**
     * Returns the typo3Mode
     *
     * @return string $typo3Mode
     */
    public function getTypo3Mode()
    {
        return $this->typo3Mode;
    }

    /**
     * Sets the typo3Mode
     *
     * @param string $typo3Mode
     * @return void
     */
    public function setTypo3Mode($typo3Mode)
    {
        $this->typo3Mode = $typo3Mode;
    }

    /**
     * Returns the user
     *
     * @return int $user
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Sets the user
     *
     * @param int $user
     * @return void
     */
    public function setUser($user)
    {
        $this->user = $user;
    }

    /**
     * Returns the page
     *
     * @return int $page
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Sets the page
     *
     * @param int $page
     * @return void
     */
    public function setPage($page)
    {
        $this->page = $page;
    }

}