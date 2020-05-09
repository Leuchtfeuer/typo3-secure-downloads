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

use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\AbstractToken;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractCheck implements SingletonInterface
{
    /**
     * @var ExtensionConfiguration
     */
    protected $extensionConfiguration;

    /**
     * @var AbstractToken
     */
    protected $token;

    /**
     * @var UserAspect
     */
    protected $userAspect;

    public function __construct(ExtensionConfiguration $extensionConfiguration)
    {
        $this->extensionConfiguration = $extensionConfiguration;
    }

    public function setToken(AbstractToken $token)
    {
        $this->token = $token;
        $this->userAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');
    }

    abstract public function hasAccess(): bool;

    protected function isFileCoveredByGroupCheck(): bool
    {
        if (!$this->extensionConfiguration->isEnableGroupCheck()) {
            // Return false because group check is disabled therefore the access is not covered by user groups
            return false;
        }

        $groupCheckDirectories = $this->extensionConfiguration->getGroupCheckDirs();

        if (empty($groupCheckDirectories) || $groupCheckDirectories === ExtensionConfiguration::FILE_TYPES_WILDCARD) {
            // Return true because group check is enabled and all protected directories are covered by the check
            return true;
        }

        return (bool)preg_match(sprintf('#(%s)#i', $groupCheckDirectories), $this->token->getFile());
    }
}