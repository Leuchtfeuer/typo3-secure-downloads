<?php
namespace Bitmotion\SecureDownloads\Resource\Publishing;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Bitmotion GmbH (typo3-ext@bitmotion.de)
 *
 *  All rights reserved
 *
 *  This script is part of the Typo3 project. The Typo3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
use Bitmotion\SecureDownloads\Parser\HtmlParser;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class PhpDeliveryProtectedResourcePublishingTarget
 * @package Bitmotion\SecureDownloads\Resource\Publishing
 */
class PhpDeliveryProtectedResourcePublishingTarget extends AbstractResourcePublishingTarget
{
    /**
     * Publishes a persistent resource to the web accessible resources directory
     *
     * @param ResourceInterface $resource The resource to publish
     *
     * @return mixed Either the web URI of the published resource or FALSE if the resource source file doesn't exist or
     *     the resource could not be published for other reasons
     */
    public function publishResource(ResourceInterface $resource)
    {
        $publicUrl = false;
        // We only manipulate the URL if we are in the backend or in FAL mode in FE (otherwise we parse the HTML)
        if (!$this->getRequestContext()->isFrontendRequest()) {
            $this->setResourcesSourcePath($this->getResourcesSourcePathByResourceStorage($resource->getStorage()));
            if ($this->isSourcePathInDocumentRoot()) {
                if (!$this->isPubliclyAvailable($resource)) {
                    $publicUrl = $this->buildUri($this->getResourceUri($resource));
                }
            } else {
                // TODO: Maybe implement this case?
                // We need to use absolute paths then or copy the files around, or...
            }
        }

        return $publicUrl;
    }

    /**
     * Checks if a resource which lies in document root is really publicly available
     * This is currently only done by checking configured secure paths, not by requesting the resources
     *
     * @param ResourceInterface $resource
     *
     * @return bool
     */
    protected function isPubliclyAvailable(ResourceInterface $resource)
    {
        $resourceUri = $this->getResourceUri($resource);
        $securedFoldersExpression = $this->configurationManager->getValue('securedDirs');
        if (substr($this->configurationManager->getValue('securedFiletypes'), 0, 1) === '\\') {
            $fileExtensionExpression = $this->configurationManager->getValue('securedFiletypes');
        } else {
            $fileExtensionExpression = '\\.(' . $this->configurationManager->getValue('securedFiletypes') . ')';
        }

        // TODO: maybe check if the resource is available without authentication by doing a head request
        return !(preg_match('/((' . HtmlParser::softQuoteExpression($securedFoldersExpression) . ')+?\/.*?(?:(?i)' . ($fileExtensionExpression) . '))/i',
                $resourceUri, $matchedUrls) && is_array($matchedUrls) && $matchedUrls[0] === $resourceUri);
    }

    /**
     * Builds a URI which uses a PHP Script to access the resource
     * by taking several parameters into account
     *
     * @param string $resourceUri
     *
     * @return string
     */
    protected function buildUri($resourceUri)
    {
        $userId = $this->getRequestContext()->getUserId();
        $userGroupIds = $this->getRequestContext()->getUserGroupIds();
        $validityPeriod = $this->calculateLinkLifetime();
        $hash = $this->getHash($resourceUri, $userId, $userGroupIds, $validityPeriod);

        $linkFormat = $this->configurationManager->getValue('linkFormat');
        // Parsing the link format, and return this instead (an flexible link format is useful for mod_rewrite tricks ;)
        if (is_null($linkFormat) || strpos($linkFormat, '###FEGROUPS###') === false) {
            $linkFormat = 'index.php?eID=tx_securedownloads&p=###PAGE###&u=###FEUSER###&g=###FEGROUPS###&t=###TIMEOUT###&hash=###HASH###&file=###FILE###';
        }
        $tokens = ['###FEUSER###', '###FEGROUPS###', '###FILE###', '###TIMEOUT###', '###HASH###', '###PAGE###'];
        $replacements = [
            $userId,
            rawurlencode(implode(',', $userGroupIds)),
            str_replace('%2F', '/', rawurlencode($resourceUri)),
            $validityPeriod,
            $hash,
            $GLOBALS['TSFE']->id,
        ];
        $downloadUri = str_replace($tokens, $replacements, $linkFormat);

        return $downloadUri;
    }

    /**
     * @return integer
     */
    protected function calculateLinkLifetime()
    {
        $lifeTimeToAdd = $this->configurationManager->getValue('cachetimeadd');

        if ($this->getRequestContext()->getCacheLifetime() === 0) {
            $validityPeriod = 86400 + $GLOBALS['EXEC_TIME'] + $lifeTimeToAdd;
        } else {
            $validityPeriod = $this->getRequestContext()->getCacheLifetime() + $GLOBALS['EXEC_TIME'] + $lifeTimeToAdd;
        }

        return $validityPeriod;
    }

    /**
     * @param string $resourceUri
     * @param integer $userId
     * @param         array <integer> $userGroupIds
     * @param integer $validityPeriod
     *
     * @return string
     */
    protected function getHash($resourceUri, $userId, array $userGroupIds, $validityPeriod)
    {
        if ($this->configurationManager->getValue('enableGroupCheck')) {
            $hashString = $userId . implode(',', $userGroupIds) . $resourceUri . $validityPeriod;
        } else {
            $hashString = $userId . $resourceUri . $validityPeriod;
        }

        return GeneralUtility::hmac($hashString, 'bitmotion_securedownload');
    }

    /**
     * Builds a URI which uses a PHP Script to access the resource
     *
     * @param string $resourceUri
     *
     * @return string
     */
    public function publishResourceUri($resourceUri)
    {
        $this->setResourcesSourcePath(PATH_site);

        return $this->buildUri($resourceUri);
    }
}
