<?php
declare(strict_types=1);
namespace Bitmotion\SecureDownloads\Domain\Transfer;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
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

    public function setPageId(int $pageId): void
    {
        $this->pageId = $pageId;
    }

    public function getFileType(): string
    {
        return $this->fileType;
    }

    public function setFileType(string $fileType): void
    {
        $this->fileType = $fileType;
    }

    public function getFrom(): ?int
    {
        return $this->formatDate($this->from);
    }

    public function setFrom(string $from): void
    {
        $this->from = $from;
    }

    private function formatDate(string $dateString): ?int
    {
        if ($dateString == '') {
            return null;
        }

        list($date, $time) = explode(' ', trim($dateString));

        if ($date == '') {
            list($year, $month, $day) = explode('-', $dateString);
        } else {
            list($year, $month, $day) = explode('-', $date);
            list($hour, $minute, $second) = explode(':', $time);
        }

        $dateTime = new \DateTime();
        $dateTime->setDate($year, (int)$month, (int)$day);

        if (isset($hour) && isset($minute) && isset($second)) {
            $dateTime->setTime((int)$hour, (int)$minute, (int)$second);
        }

        return $dateTime->getTimestamp();
    }

    public function getTill(): ?int
    {
        return $this->formatDate($this->till);
    }

    public function setTill(string $till): void
    {
        $this->till = $till;
    }

    public function getFeUserId(): int
    {
        return $this->feUserId;
    }

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

    public function setUserType(int $userType): void
    {
        $this->userType = $userType;
    }
}
