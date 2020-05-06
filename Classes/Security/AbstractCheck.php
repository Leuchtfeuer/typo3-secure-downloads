<?php

namespace Leuchtfeuer\SecureDownloads\Security;

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
