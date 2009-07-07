<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Peter Ruijter (peter.ruijter at bedrijvenweb nl)
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
 * @author	Peter Ruijter <peter.ruijter at bedrijvenweb nl>
 * @author	Dietrich Heise <>
 */

class ux_fileList extends fileList {

	/**
	 * Wraps filenames in links which opens them in a window IF they are in web-path.
	 *
	 * @param	string		String to be wrapped in links
	 * @param	string		ID (path)
	 * @return	string		HTML
	 */
	function linkWrapFile($code,$path)	{
		include_once('class.tx_nawsecuredl.php');
		$tmpobj = t3lib_div::makeInstance('tx_nawsecuredl');

		if (t3lib_div::isFirstPartOfStr($path,PATH_site))	{
			$href = t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $tmpobj->makeSecure(substr($path, strlen(PATH_site)));
			$aOnClick = "return top.openUrlInWindow('".$href."','WebFile');";
			$code = '<a href="#" title="'.htmlspecialchars($code).'" onclick="'.$aOnClick.'">'.t3lib_div::fixed_lgd($code,$this->fixedL).'</a>';
		}
		return $code;
	}
}
?>