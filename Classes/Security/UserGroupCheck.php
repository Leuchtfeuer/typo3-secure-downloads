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

use TYPO3\CMS\Core\Utility\GeneralUtility;

class UserGroupCheck extends AbstractCheck
{
    public function hasAccess(): bool
    {
        if (!$this->extensionConfiguration->isEnableGroupCheck()) {
            return true;
        }

        $groupCheckDirs = $this->extensionConfiguration->getGroupCheckDirs();

        if (!empty($groupCheckDirs) && !preg_match('/' . $this->softQuoteExpression($groupCheckDirs) . '/', $this->download->getFile())) {
            return false;
        }

        $actualGroups = $this->userAspect->get('groupIds');
        $transmittedGroups = $this->download->getGroups();
        sort($actualGroups);
        sort($transmittedGroups);

        if ($actualGroups === $transmittedGroups) {
            // Actual groups and transmitted groups are identically, so we can ignore the excluded groups
            return true;
        }

        if ($this->extensionConfiguration->isStrictGroupCheck()) {
            // Groups are not identically. Deny access when strict group access is enabled.
            return false;
        }

        $excludedGroups = GeneralUtility::intExplode(',', $this->extensionConfiguration->getExcludeGroups(), true);
        $verifiableGroups = array_diff($actualGroups, $excludedGroups);

        foreach ($verifiableGroups as $actualGroup) {
            if (in_array($actualGroup, $transmittedGroups, true)) {
                return true;
            }
        }

        return false;
    }

    protected function softQuoteExpression(string $string): string
    {
        $string = str_replace('\\', '\\\\', $string);
        $string = str_replace(' ', '\ ', $string);
        $string = str_replace('/', '\/', $string);
        $string = str_replace('.', '\.', $string);
        $string = str_replace(':', '\:', $string);

        return $string;
    }
}
