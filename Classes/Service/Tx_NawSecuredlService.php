<?php
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

include_once('SecureDownloadService.php');

/**
 * @deprecated
 * @author Dietrich Heise <typo3-ext(at)bitmotion.de>
 */
class Tx_NawSecureDownloadService extends \Bitmotion\NawSecuredl\Service\SecureDownloadService {

}

// Include extension?
// Deprecated, only used for TYPO3 < 6.0
if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/naw_securedl/Classes/Driver/Xclass/Tx_NawSecureDownloadService.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/naw_securedl/Classes/Driver/Xclass/Tx_NawSecureDownloadService.php']);
}
?>