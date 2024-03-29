<?php

declare(strict_types=1);
namespace Leuchtfeuer\SecureDownloads\Security;

use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;

/***
 *
 * This file is part of the "Secure Downloads" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2020 Dev <dev@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

class UserCheck extends AbstractCheck
{
    /**
     * @inheritDoc
     *
     * @return bool
     * @throws AspectPropertyNotFoundException
     */
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
