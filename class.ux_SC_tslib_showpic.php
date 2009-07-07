<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2007 Dietrich Heise (typo3-ext(at)naw.info)
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
 * @author	Dietrich Heise <typo3-ext(at)naw.info>
 */

class ux_SC_tslib_showpic extends SC_tslib_showpic {
	function printContent()	{
		include_once('class.tx_nawsecuredl.php');
		$tmpobj = t3lib_div::makeInstance('tx_nawsecuredl');
		echo $tmpobj->parseContent($this->content);
	}
}
?>