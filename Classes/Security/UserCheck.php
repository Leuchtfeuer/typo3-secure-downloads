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

class UserCheck extends AbstractCheck
{
    public function hasAccess(): bool
    {
        $user = $this->token->getUser();

        if (!$this->extensionConfiguration->isAllowPublicAccess() && $user === 0) {
            return false;
        }

        if ($this->isFileCoveredByGroupCheck() || $user === 0) {
            return true;
        }

        return $user === $this->userAspect->get('id');
    }
}
