<?php
namespace Bitmotion\NawSecuredl\TYPO3\CMS\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Dietrich Heise (typo3-ext(at)bitmotion.de)
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

/**
 * Fixes the image controller until the bug itself is fixed in TYPO3
 */
class ShowImageController extends \TYPO3\CMS\Frontend\Controller\ShowImageController {
	/**
	 * Outputs the content from $this->content
	 *
	 * @return void
	 */
	public function printContent() {
		$SecureDownloadService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('Bitmotion\NawSecuredl\Service\SecureDownloadService');
		echo $SecureDownloadService->parseContent($this->content);
	}
}