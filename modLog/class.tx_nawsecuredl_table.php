<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Helmut Hummel <typo3-ext(at)naw.info>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
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
 *
 *
 * borrowed from http://www.bytemycode.com/snippets/snippet/419/
 * @author hummel
 *
 */

/* Usage Example

$tb = new myTable;

// set up the style of the table... only needed if you don't like the defaults
// You could setup include files for each style of table you need and just include
// the particular style you want here.
$tb->numCols = 3;     // Required
$tb->tableWidth = "90%";
$tb->tablestyle = "background: #000000;";
$tb->rowLightStyle = "background: #FFFFFF; color: #000000; font-size: 12px;";
$tb->rowDarkStyle = "background: #EBEBEB; color: #000000; font-size: 12px;";
$tb->rowHighlightStyle = "background: #FFFF99; color: #000000; font-size: 12px;";
$tb->cellStyle = "";
$tb->cellspacing = 1;
$tb->cellpadding = 1;
$tb->headings = array("Item","Category", "Quantity");    // Required

$newRow = array("20x20 Tent","Tents","5");
$tb->addRow($newRow);

$newRow = array("8ft Table", "Tables", "15");
$tb->addRow($newRow);

$newrow = array("Silver Chair", "Chairs", "300");
$tb->addRow($newRow);

// I think the above method reads easier than the following esspecially if
// you are using a lot of variables
// $tb->addRow( array("20x20 Tent","Tents","5") );

$tb->displayTable();
*/

class tx_nawsecuredl_table {

	var $numCols;
	var $headings;    // array of the headings $object->headings = array('Business','Contact Name');

	// table style - Setting font sizes decorations etc...
	var $headingStyle;      // style for the heading row
	var $rowLightStyle;     // style for the lighter colored rows
	var $rowDarkStyle;      // style for the darker colored rows
	var $rowHighlightStyle; // style for the highlighted row

	var $colStyle;   // array of style tags for each column (not implemented)

	// basic table settings
	var $cellpadding = 3;
	var $cellspacing = 0;
	var $tableWidth;
	var $tablestyle = 'border-collapse: collapse';

	// cell borders
	var $cellStyle;        // another style tag for each cell


	var $row;              // array $row[row][column]
	var $numRows = 0;      // row counter
	// Constructor
	public function __construct($numCols = 4)
	{
		// this sets up the default values
		// if all of your tables are going to look the same you can put all
		// of your style information in here and it will make using the table class easier
		$this->headingStyle = "font-weight: bold; background: #999; color: #eee; font-size: 11px;";

		$this->rowLightStyle = "background: #FFFFFF; color: #000000; font-size: 10pt;";
		$this->rowDarkStyle = "background: #DDDDDD; color: #000000; font-size: 10pt;";

		$this->cellStyle = "border: solid 1px #000000; vertical-align: top; width: 25%;";

		$this->numCols = $numCols;
	}


	public function setHeader($rowData)
	{
		$this->headings = $rowData;
	}


	public function addRow($rowData, $escape = true)
	{

		for ( $i = 0; $i < $this->numCols; $i++ ){
			if ($rowData[$i]) {
				$this->row[$this->numRows][$i] = $escape ? htmlspecialchars($rowData[$i], ENT_QUOTES, $GLOBALS['LANG']->charSet) : $rowData[$i];
			} else {
				$this->row[$this->numRows][$i] = "&nbsp;";
			}
		}

		$this->numRows++;
	}

	public function render()
	{

		$out .=  "<style>\r\n";
		$out .=  ".row_dark { " . $this->rowDarkStyle . " }\r\n";
		$out .=  ".row_lite { " . $this->rowLightStylestyle . " }\r\n";
		$out .=  ".row_hilite { " . $this->rowHighlightStyle . " } \r\n";
		$out .=  "th { " . $this->headingStyle . " } \r\n";
		$out .=  ".table-cell-3,.table-cell-2 { text-align: right; } \r\n";
		$out .=  "</style>\r\n";

		$out .=  '<table cellspacing="' . $this->cellspacing . '" cellpadding="' . $this->cellpadding . '" width="' . $this->tableWidth . '" style="' . $this->tablestyle . '">'."\r\n";
		// The heading row
		$out .=  "<tr style=\"" . $this->headingStyle . "\">\r\n<thead>\r\n";

		for ( $i=0; $i < $this->numCols; $i++ ){
			$out .=  "<th style=\"" . $this->cellStyle . "\">" . $this->headings[$i] . "</th>";
		}

		$out .=  "\r\n</thead>\r\n</tr>\r\n<tbody>\r\n";

		for ( $r=0; $r<$this->numRows; $r++ ){

			if (fmod($r,2)) {// Even rows are lite, odd rows are dark
				$cRowClass = "row_dark";
			} else {
				$cRowClass = "row_lite";
			}
			$out .=  "<tr class=\"" . $cRowClass . "\"";

			if ($this->row_hilite_style) {
				$out .=  " onmouseover=\"this.className='" . $cRowClass . " row_hilite';\" onmouseout=\"this.className='" . $cRowClass . "';\"";
			}
			$out .=  ">\r\n";
			for ( $c=0; $c<$this->numCols; $c++ ){
					$out .=  '<td class="table-cell-'.$c.'" style="' . $this->cellStyle . '">' . $this->row[$r][$c] . '</td>';
			}
			$out .=  "\r\n</tr>\r\n";
		}

		$out .=  "\r\n</tbody>\r\n</table>";
		return $out;
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/naw_securedl/modLog/class.tx_nawsecuredl_table.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/naw_securedl/modLog/class.tx_nawsecuredl_table.php']);
}

?>