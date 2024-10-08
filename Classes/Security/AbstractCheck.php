<?php

declare(strict_types=1);

/*
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * (c) Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\SecureDownloads\Security;

use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;
use Leuchtfeuer\SecureDownloads\Domain\Transfer\Token\AbstractToken;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractCheck
{
    protected ExtensionConfiguration $extensionConfiguration;
    protected AbstractToken $token;
    protected UserAspect $userAspect;

    public function __construct()
    {
        // Do not use DI as container is not available during enabling this extension
        $this->extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
    }

    /**
     * @param AbstractToken $token The JSON web token
     * @throws AspectNotFoundException
     */
    public function setToken(AbstractToken $token): void
    {
        $this->token = $token;
        $this->userAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');
    }

    /**
     * Checks the access of an user to the file.
     *
     * @return bool True when the current user has access to the file, false if not
     */
    abstract public function hasAccess(): bool;

    /**
     * Checks whether the actual file is covered by any user group check.
     *
     * @return bool True when the actual file is covered by a group check, false if not
     */
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
