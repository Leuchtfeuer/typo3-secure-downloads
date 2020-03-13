<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Domain\Model;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

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
     * @var string
     */
    protected $protected = '';

    /**
     * @var string
     */
    protected $host = '';

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

    public function setTstamp(int $tstamp): void
    {
        $this->tstamp = $tstamp;
    }

    public function getUserObject(): ?array
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
            'protected' => $this->getProtected(),
            'host' => $this->getHost(),
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

    public function setFileId(string $fileId): void
    {
        $this->fileId = $fileId;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function getFileSize(): int
    {
        return $this->fileSize;
    }

    public function setFileSize(int $fileSize): void
    {
        $this->fileSize = $fileSize;
    }

    public function getFileType(): string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType): void
    {
        $this->fileType = $fileType;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function setMediaType(string $mediaType): void
    {
        $this->mediaType = $mediaType;
    }

    public function getProtected(): string
    {
        return $this->protected;
    }

    public function setProtected(string $protected): void
    {
        $this->protected = $protected;
    }

    public function getHost(): string
    {
        return $this->host;
    }

    public function setHost(string $host): void
    {
        $this->host = $host;
    }

    public function getUser(): int
    {
        return (int)$this->user;
    }

    public function setUser(int $user): void
    {
        $this->user = $user;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }
}
