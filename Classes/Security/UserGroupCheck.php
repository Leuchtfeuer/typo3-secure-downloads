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

use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserGroupCheck extends AbstractCheck
{
    /**
     * @inheritDoc
     *
     * @throws AspectPropertyNotFoundException
     */
    public function hasAccess(): bool
    {
        if (!$this->isFileCoveredByGroupCheck()) {
            // Grant access if group check is disabled or file is not covered by group check
            return true;
        }

        $actualGroups = $this->userAspect->get('groupIds');
        $transmittedGroups = $this->token->getGroups();
        sort($actualGroups);
        sort($transmittedGroups);

        if ($actualGroups === $transmittedGroups) {
            // Actual groups and transmitted groups are identically, so we can ignore the excluded groups
            return true;
        }

        if ($this->extensionConfiguration->isStrictGroupCheck()) {
            // File groups are a real subset of the users groups -> return true, else return false
            return array_intersect($actualGroups, $transmittedGroups) == $actualGroups;
        }

        return $this->performStrictGroupCheck($actualGroups, $transmittedGroups);
    }

    /**
     * Checks whether one of the actual groups is in the transmitted groups.
     *
     * @param array<int> $actualGroups      The user groups the link was generated for
     * @param array<int> $transmittedGroups The actual user groups of the user that tries to download the file
     *
     * @return bool True if one group matches, false if no groups are matching
     */
    protected function performStrictGroupCheck(array $actualGroups, array $transmittedGroups): bool
    {
        $excludedGroups = GeneralUtility::intExplode(',', $this->extensionConfiguration->getExcludeGroups(), true);
        $verifiableGroups = array_diff($actualGroups, $excludedGroups);

        foreach ($verifiableGroups as $actualGroup) {
            if (in_array($actualGroup, $transmittedGroups, true)) {
                return true;
            }
        }

        return false;
    }
}
