<?php
namespace Bm\Securedl\Driver\Xclass;

/**
 * Created by JetBrains PhpStorm.
 * User: heise
 * Date: 18.07.13
 * Time: 15:51
 * To change this template use File | Settings | File Templates.
 */

class LocalDriver extends \TYPO3\CMS\Core\Resource\Driver\LocalDriver {

	/**
	 * Returns a publicly accessible URL for a file.
	 *
	 * WARNING: Access to the file may be restricted by further means, e.g.
	 * some web-based authentication. You have to take care of this yourself.
	 *
	 * @param \TYPO3\CMS\Core\Resource\ResourceInterface $resourceObject The file or folder object
	 * @param bool $relativeToCurrentScript Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
	 * @return string
	 */
	public function getPublicUrl(\TYPO3\CMS\Core\Resource\ResourceInterface $resourceObject, $relativeToCurrentScript = FALSE) {
		$publicUrl = parent::getPublicUrl($resourceObject, $relativeToCurrentScript = FALSE);

		/*
		 * Only used in the BE Mode, the FE does a htmlspecialchars() somewhere
		 */
		if (TYPO3_MODE == 'BE'){
			$extensionConfiguration = $this->getSecureDlExtensionConfiguration();

			if (preg_match('/(('. $this->softQuoteExpression($extensionConfiguration['securedDirs']) . ')+?\/.*?(?:(?i)' . ($extensionConfiguration['filetype']) . '))/i', $publicUrl, $matchedUrls)) {
				if (is_array($matchedUrls)){
					if ($matchedUrls[0] == $publicUrl){
						$objSecureDownloads = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance
							('Bm\Securedl\Service\SecuredlService');
						$publicUrl = $objSecureDownloads->makeSecure($publicUrl);
						// TODO: search better solution
						if ( substr($publicUrl,0,1) != '/' ){
							$publicUrl = '/' . $publicUrl;
						}
					}
				}
			}
		}

		return $publicUrl;
	}

	/**
	 * Get extension configuration
	 *
	 * @return array
	 */
	protected function getSecureDlExtensionConfiguration() {
		return unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['naw_securedl']);
	}

	/**
	 * Quotes special some characters for the regular expression.
	 * Leave braces and brackets as is to have more flexibility in configuration.
	 *
	 * @param string $string
	 * @return string
	 */
	protected function softQuoteExpression($string) {
		$string = str_replace('\\', '\\\\', $string);
		$string = str_replace(' ', '\ ', $string);
		$string = str_replace('/', '\/', $string);
		$string = str_replace('.', '\.', $string);
		$string = str_replace(':', '\:', $string);
		return $string;
	}
}