<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Security;

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
use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractCheck
{
    /**
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

    /**
     * @var Download
     */
    protected $download;

    /**
     * @var UserAspect
     */
    protected $userAspect;

    public function __construct(ExtensionConfiguration $extensionConfiguration, Download $download)
    {
        $this->extensionConfiguration = $extensionConfiguration;
        $this->download = $download;
        $this->userAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');
    }

    abstract public function hasAccess(): bool;
}
