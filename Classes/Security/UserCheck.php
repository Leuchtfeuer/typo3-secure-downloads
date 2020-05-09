<?php
declare(strict_types = 1);
namespace Leuchtfeuer\SecureDownloads\Security;

use Leuchtfeuer\SecureDownloads\Domain\Transfer\ExtensionConfiguration;

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

class UserCheck extends AbstractCheck
{
    public function hasAccess(): bool
    {
        if ($this->isFileCoveredByGroupCheck() || $this->token->getUser() === 0) {
            return true;
        }

        return $this->token->getUser() === $this->userAspect->get('id');
    }

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

        return (bool)preg_match('/' . $this->softQuoteExpression($groupCheckDirectories) . '/', $this->token->getFile());
    }
}
