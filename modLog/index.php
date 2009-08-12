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


	// DEFAULT initialization of a module [BEGIN]
unset($MCONF);
require_once('conf.php');
require_once($BACK_PATH.'init.php');
require_once($BACK_PATH.'template.php');

$LANG->includeLLFile('EXT:naw_securedl/modLog/locallang.xml');
require_once(PATH_t3lib.'class.t3lib_scbase.php');
$BE_USER->modAccess($MCONF,1);    // This checks permissions and exits if the users has no permission for entry.
	// DEFAULT initialization of a module [END]



/**
 * Module 'Download Log' for the 'naw_securedl' extension.
 *
 * @author    Helmut Hummel <typo3-ext(at)naw.info>
 * @package    TYPO3
 * @subpackage    tx_nawsecuredl
 */
class  tx_nawsecuredl_module1 extends t3lib_SCbase {
	var $pageinfo;

	/**
	 * Initializes the Module
	 * @return    void
	 */
	function init()
	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		parent::init();

		$this->include_once[] = realpath(dirname(__FILE__).'/class.tx_nawsecuredl_table.php');

		/*
		if (t3lib_div::_GP('clear_all_cache'))    {
			$this->include_once[] = PATH_t3lib.'class.t3lib_tcemain.php';
		}
		*/
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return    void
	 */
	function menuConfig()    {
		global $LANG;
		$this->MOD_MENU = array (
			'users' => array (
				'-1' => $LANG->getLL('loggedInUsers'),
				'0' => $LANG->getLL('notLoggedIn'),
				'' => '------------------------------',
			),
			'mode' => array (
				'-1' => $LANG->getLL('allTime'),
				'1' => $LANG->getLL('byTime'),
			),
		);

		foreach (t3lib_BEfunc::getRecordsByField('fe_users', 1, 1) as $user) {
			$this->MOD_MENU['users'][$user['uid']] = $user['username'];
		}

		parent::menuConfig();

		$set = t3lib_div::_GP('SET');

		if ($set['time']) {
			$dateFrom = strtotime($set['time']['from']);
			$dateTo = strtotime($set['time']['to']);

			$set['time']['from'] = ($dateFrom > 0) ? date('d.m.Y', $dateFrom) : '';
			$set['time']['to'] = ($dateTo > 0) ? date('d.m.Y', $dateTo) : '';

			$mergedSettings = t3lib_div::array_merge($this->MOD_SETTINGS, $set);

			$GLOBALS['BE_USER']->pushModuleData($this->MCONF['name'], $mergedSettings);
			$this->MOD_SETTINGS = $mergedSettings;
		}
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 * If you chose "web" as main module, you will need to consider the $this->id parameter which will contain the uid-number of the page clicked in the page tree
	 *
	 * @return    [type]        ...
	 */
	function main()
	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		// Access check!
		// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$this->perms_clause);
		$access = is_array($this->pageinfo) ? 1 : 0;

		if (($this->id && $access) || ($BE_USER->user['admin'] && !$this->id))    {

				// Draw the header.
			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;
			$this->doc->form='<form action="" method="POST">';

				// JavaScript
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL)    {
						document.location = URL;
					}
				</script>
			';
			$this->doc->postCode='
				<script language="javascript" type="text/javascript">
					script_ended = 1;
					if (top.fsMod) top.fsMod.recentIds["web"] = 0;
				</script>
			';

			$headerSection = $this->doc->getHeader('pages',$this->pageinfo,$this->pageinfo['_thePath']).'<br />'.$LANG->sL('LLL:EXT:lang/locallang_core.xml:labels.path').': '.t3lib_div::fixed_lgd_pre($this->pageinfo['_thePath'],50);

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->section('',$this->doc->funcMenu($headerSection,t3lib_BEfunc::getFuncMenu($this->id,'SET[mode]',$this->MOD_SETTINGS['mode'],$this->MOD_MENU['mode'])));
			$this->content.=$this->doc->section('',$this->doc->funcMenu('',t3lib_BEfunc::getFuncMenu($this->id,'SET[users]',$this->MOD_SETTINGS['users'],$this->MOD_MENU['users'])));


			if (1 == (int)$this->MOD_SETTINGS['mode']) {
				$this->content.= '<br />
				<table cellspacing="0" cellpadding="0" border="0" width="100%" id="typo3-funcmenu">
				<tbody><tr>
					<td nowrap="nowrap" valign="top"/>
					<td nowrap="nowrap" align="right" valign="top">
					'.$LANG->getLL('from').':&nbsp;
					<input name="SET[time][from]" value="'.htmlspecialchars($this->MOD_SETTINGS['time']['from']).'" />
					</td>
				</tr>
				</tbody></table>
				';
				$this->content.= '<br />
				<table cellspacing="0" cellpadding="0" border="0" width="100%" id="typo3-funcmenu">
				<tbody><tr>
					<td nowrap="nowrap" valign="top"/>
					<td nowrap="nowrap" align="right" valign="top">
					'.$LANG->getLL('to').':&nbsp;
					<input name="SET[time][to]" value="'.htmlspecialchars($this->MOD_SETTINGS['time']['to']).'" />
					</td>
				</tr>
				</tbody></table>
				';
				$this->content.= '<br />
				<table cellspacing="0" cellpadding="0" border="0" width="100%" id="typo3-funcmenu">
				<tbody><tr>
					<td nowrap="nowrap" valign="top"/>
					<td nowrap="nowrap" align="right" valign="top">
					<input type="submit" value="'.$LANG->getLL('submit').'" />
					</td>
				</tr>
				</tbody></table>
				';
			}

			$this->content.=$this->doc->divider(5);


			// Render content:
			$this->moduleContent();


			// ShortCut
			if ($BE_USER->mayMakeShortcut())    {
				$this->content.=$this->doc->spacer(20).$this->doc->section('',$this->doc->makeShortcutIcon('id',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name']));
			}

			$this->content.=$this->doc->spacer(10);
		} else {
				// If no access or if ID == zero

			$this->doc = t3lib_div::makeInstance('mediumDoc');
			$this->doc->backPath = $BACK_PATH;

			$this->content.=$this->doc->startPage($LANG->getLL('title'));
			$this->content.=$this->doc->header($LANG->getLL('title'));
			$this->content.=$this->doc->spacer(5);
			$this->content.=$this->doc->spacer(10);
		}
	}

	/**
	 * Prints out the module HTML
	 *
	 * @return    void
	 */
	function printContent()
	{
		$this->content.=$this->doc->endPage();
		echo $this->content;
	}

	/**
	 * Generates the module content
	 *
	 * @return    void
	 */
	function moduleContent()
	{
		if (-1 == $this->MOD_SETTINGS['mode']) {
			$time = null;
		} else {
			$time = $this->MOD_SETTINGS['time'];
		}
		$content = $this->getDownloadTrafficTable((int)$this->MOD_SETTINGS['users'], $time);
		$this->content.=$this->doc->section($GLOBALS['LANG']->getLL('trafficUsed').':',$content,0,1);
	}



	protected function getDownloadTrafficTable($userId, $time)
	{
		global $LANG;

		$dateFrom = strtotime($time['from']);
		$dateTo = strtotime($time['to']);


		$arrFromTables[] = 'tx_nawsecuredl_counter';
		$arrSelectFields[] = 'GROUP_CONCAT(DISTINCT tx_nawsecuredl_counter.file_name) AS files';
		$arrSelectFields[] = 'sum(tx_nawsecuredl_counter.file_size) AS traffic';
		$arrSelectFields[] = 'FROM_UNIXTIME(tx_nawsecuredl_counter.tstamp,\'%d.%m.%Y\') AS date';
		$arrGroupBy = array();
		$arrOrderBy = array();

		if ($userId > 0 OR $userId == -1) {
			$arrOrderBy[] = 'username';
			$arrSelectFields[] = 'fe_users.username AS username';
			$arrAndConditions[] = 'fe_users.uid = tx_nawsecuredl_counter.user_id';
			$arrFromTables[] = 'fe_users';
			$arrGroupBy[] = 'username';
		}
		$arrOrderBy[] = 'tx_nawsecuredl_counter.tstamp';

		if ($userId != -1) {
			$arrAndConditions[] = 'tx_nawsecuredl_counter.user_id='.(int)$userId;
		}

		$arrGroupBy[] = 'date';

		if ($dateFrom > 0) {
			$arrAndConditions[] = 'tx_nawsecuredl_counter.tstamp >= '.$dateFrom;
		}
		if ($dateTo > 0) {
			$arrAndConditions[] = 'tx_nawsecuredl_counter.tstamp < '.($dateTo + 86400);
		}

		$lines = array();

		$rows = self::getRecords(implode(',', $arrFromTables), implode(',',$arrSelectFields), implode(' AND ', $arrAndConditions), implode(',', $arrGroupBy), implode(',', $arrOrderBy));

		if ($rows) {

			/* @var $table tx_nawsecuredl_table */
			$table = t3lib_div::makeInstance('tx_nawsecuredl_table');
			$table->setHeader(array($LANG->getLL('user'),$LANG->getLL('files'),$LANG->getLL('date'),$LANG->getLL('traffic')));

			$sum = 0;
			foreach ($rows as $row) {
				$table->addRow(array($this->HtmlEscape($row['username']), $this->FormatFiles($row['files']), $this->HtmlEscape($row['date']), $this->FormatTraffic($row['traffic'])), false);
				$sum += $row['traffic'];
			}
			$table->addRow(array('','','<strong>'.$LANG->getLL('trafficsum').':</strong>','<strong>'.$this->FormatTraffic($sum).'</strong>'), false);

		} else {
			return $LANG->getLL('noTrafficUsed');
		}
		return $table->render();
	}


	protected function FormatFiles($files)
	{
		$arrFiles = t3lib_div::trimExplode(',', $files);
		foreach ($arrFiles as &$file) {
			$file = $this->HtmlEscape(basename($file));
		}

		return implode('<br />', $arrFiles);
	}

	protected function FormatTraffic($value)
	{
		return $this->HtmlEscape(sprintf('%01.2F',(double)$value / (1024*1024*1024)));

	}

	private function HtmlEscape($string)
	{
		return htmlspecialchars($string, ENT_QUOTES, $GLOBALS['LANG']->charSet);
	}

	/**
	 * Returns records from table, $theTable, where a field ($theField) equals the value, $theValue
	 * The records are returned in an array
	 * If no records were selected, the function returns nothing
	 * Usage: 8
	 *
	 * @param	string		Table name present in $TCA
	 * @param	string		Field to select on
	 * @param	string		Value that $theField must match
	 * @param	string		Optional additional WHERE clauses put in the end of the query. DO NOT PUT IN GROUP BY, ORDER BY or LIMIT!
	 * @param	string		Optional GROUP BY field(s), if none, supply blank string.
	 * @param	string		Optional ORDER BY field(s), if none, supply blank string.
	 * @param	string		Optional LIMIT value ([begin,]max), if none, supply blank string.
	 * @param	boolean		Use the deleteClause to check if a record is deleted (default true)
	 * @return	mixed		Multidimensional array with selected records (if any is selected)
	 */
	protected static function getRecords($theTable, $fields, $whereClause = '', $groupBy = '', $orderBy = '', $limit = '', $useDeleteClause = true)
	{
		#$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = true;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
					$fields ? $fields : '*',
					$theTable,
					($useDeleteClause ? t3lib_BEfunc::deleteClause($theTable).' ' : '').
						t3lib_BEfunc::versioningPlaceholderClause($theTable).' '.
						$whereClause,	// whereClauseMightContainGroupOrderBy
					$groupBy,
					$orderBy,
					$limit
				);
		$rows = array();
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$rows[] = $row;
		}
		#debug($GLOBALS['TYPO3_DB']->debug_lastBuiltQuery,"Debug of \$GLOBALS['TYPO3_DB']->debug_lastBuiltQuery"); 	//FIXME debug of $GLOBALS['TYPO3_DB']->debug_lastBuiltQuery
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return $rows;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/naw_securedl/modLog/index.php'])    {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/naw_securedl/modLog/index.php']);
}




// Make instance:
$SOBE = t3lib_div::makeInstance('tx_nawsecuredl_module1');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)    include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>