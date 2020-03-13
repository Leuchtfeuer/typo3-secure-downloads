<?php
declare(strict_types = 1);
namespace Bitmotion\SecureDownloads\Domain\Transfer;

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

class Filter
{
    const USER_TYPE_ALL = 0;
    const USER_TYPE_LOGGED_ON = -1;
    const USER_TYPE_LOGGED_OFF = -2;

    /**
     * @var string The file type.
     */
    protected $fileType = '0';

    /**
     * @var string From
     */
    protected $from = '';

    /**
     * @var string Till
     */
    protected $till = '';

    /**
     * @var int The frontend user ID
     */
    protected $feUserId = 0;

    /**
     * @var int The user type
     */
    protected $userType = 0;

    /**
     * @var int The page ID
     */
    protected $pageId = 0;

    public function getPageId(): int
    {
        return $this->pageId;
    }

    /**
     * @param int $pageId Sets the page ID
     */
    public function setPageId(int $pageId): void
    {
        $this->pageId = $pageId;
    }

    public function getFileType(): string
    {
        return $this->fileType;
    }

    /**
     * @param string $fileType Sets the file type
     */
    public function setFileType(string $fileType): void
    {
        $this->fileType = $fileType;
    }

    public function getFrom(): ?int
    {
        return $this->formatDate($this->from);
    }

    /**
     * @param string $from Sets the from time
     */
    public function setFrom(string $from): void
    {
        $this->from = $from;
    }

    private function formatDate(string $dateString): ?int
    {
        if ($dateString == '') {
            return null;
        }

        try {
            $dateTime = (new \DateTime($dateString))->getTimestamp();
        } catch (\Exception $exception) {
            $dateTime = null;
        }

        return $dateTime;
    }

    public function getTill(): ?int
    {
        return $this->formatDate($this->till);
    }

    /**
     * @param string $till Sets the till time
     */
    public function setTill(string $till): void
    {
        $this->till = $till;
    }

    public function getFeUserId(): int
    {
        return $this->feUserId;
    }

    /**
     * @param int $feUserId Sets the website user
     */
    public function setFeUserId(int $feUserId): void
    {
        switch ($feUserId) {
            case self::USER_TYPE_LOGGED_ON:
                $this->userType = self::USER_TYPE_LOGGED_ON;
                $this->feUserId = 0;
                break;

            case self::USER_TYPE_LOGGED_OFF:
                $this->userType = self::USER_TYPE_LOGGED_OFF;
                $this->feUserId = 0;
                break;

            default:
                $this->feUserId = $feUserId;
        }
    }

    public function getUserType(): int
    {
        return $this->userType;
    }

    /**
     * @param int $userType Sets the user type
     */
    public function setUserType(int $userType): void
    {
        $this->userType = $userType;
    }
}
