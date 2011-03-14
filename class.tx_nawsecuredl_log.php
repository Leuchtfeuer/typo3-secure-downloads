<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * Download logging
 *
 * @author	Rene Fritz <typo3-ext(at)naw.info>
 * @author	Helmut Hummel <typo3-ext(at)naw.info>
 * @package TYPO3
 * @subpackage naw_securedl
 */



#
# Table structure for table 'tx_dam_log_download'
#
//CREATE TABLE tx_dam_log_download (
//    uid int(11) NOT NULL auto_increment,
//    pid int(11) DEFAULT '0' NOT NULL,
//    tstamp int(11) DEFAULT '0' NOT NULL,
//    crdate int(11) DEFAULT '0' NOT NULL,
//    cruser_id int(11) DEFAULT '0' NOT NULL,
//    file_id tinytext NOT NULL,
//    file_name varchar(100) DEFAULT '' NOT NULL,
//    file_path tinytext NOT NULL,
//    file_type varchar(4) DEFAULT '' NOT NULL,
//    media_type tinyint(4) unsigned DEFAULT '0' NOT NULL,
//    file_size int(11) unsigned DEFAULT '0' NOT NULL,
//    bytes_downloaded int(11) unsigned DEFAULT '0' NOT NULL,
//    protected varchar(30) DEFAULT '' NOT NULL,
//    host varchar(30) DEFAULT '' NOT NULL,
//    user_id int(11) DEFAULT '0' NOT NULL,
//    user_group int(11) DEFAULT '0' NOT NULL,
//    page_id int(11) DEFAULT '0' NOT NULL,
//    app_id varchar(30) DEFAULT '' NOT NULL,
//    sitetitle varchar(30) DEFAULT '' NOT NULL,
//    typo3_mode char(2) DEFAULT '' NOT NULL,
//
//    PRIMARY KEY (uid),
//    KEY parent (pid)
//);


/**
 * Download logging
 * Part of the DAM (digital asset management) extension.
 *
 * @author	Rene Fritz <r.fritz@colorcube.de>
 * @package DAM-Core
 * @subpackage Lib
 */
class tx_nawsecuredl_log {

	/**
	 * Application ID to be written to the log. Can be a plugin prefix.
	 *
	 * @var string
	 */
	var $app_id = '';

	/**
	 * The name of the website. Default is from TSFE.
	 *
	 * @var string
	 */
	var $sitetitle = '';


	/**
	 * Initialize the object
	 * PHP4 constructor
	 *
	 * @param 	string 	$app_id Application ID to be written to the log. Can be a plugin prefix.
	 * @param 	string 	$sitetitle The name of the website. Default is from TSFE.
	 * @return	void
	 * @see __construct()
	 */
	function tx_nawsecuredl_log($app_id='', $sitetitle='') {
		if ($app_id) $this->init($app_id, $sitetitle);
	}


	/**
	 * Initialize the object
	 * PHP5 constructor
	 *
	 * @param 	string 	$app_id Application ID to be written to the log. Can be a plugin prefix.
	 * @param 	string 	$sitetitle The name of the website. Default is from TSFE.
	 * @return	void
	 * @see __construct()
	 */
	function &__construct($app_id='', $sitetitle='') {
		if ($app_id) $this->init($app_id, $sitetitle);
	}


	/**
	 * Init the log object
	 *
	 * @param 	string 	$app_id Application ID to be written to the log. Can be a plugin prefix.
	 * @param 	string 	$sitetitle The name of the website. Default is from TSFE.
	 * @return 	void
	 */
	function init($app_id, $sitetitle='') {
		$this->app_id = $app_id;
		$this->sitetitle = $sitetitle ? $sitetitle : (is_object($GLOBALS['TSFE']) ? $GLOBALS['TSFE']->tmpl->setup['sitetitle'] : '');
	}


	/**
	 * Insert a log entry
	 *
	 * The $addInfo array can override the defaults/detected values except the user_group which cannot be guessed.
	 *
	 * @param 	mixed  		$fileInfo see tx_dam.
	 * @param 	integer 	$bytes_downloaded
	 * @param 	boolean 	$protected Defines if it is a secure download
	 * @param 	array 		$addInfo Additional infos to be written: host, user_id, user_name, user_group, page_id
	 * @return 	integer 	Log id. See update().
	 */
	function insert($fileInfo, $bytes_downloaded=0, $protected=0, $addInfo=array()) {

		if (is_object($fileInfo)) {
			$fileInfo = $fileInfo->getMetaArray();
		} elseif (!is_array($fileInfo)) {
			$fileInfo = tx_dam::file_compileInfo ($fileInfo);
		}

		$addInfo['host'] = $addInfo['host'] ? $addInfo['host'] : t3lib_div::getIndpEnv('REMOTE_ADDR');

		if(!$addInfo['user_id']) {
			if (is_object($GLOBALS['TSFE'])) {
				$addInfo['user_id'] = $GLOBALS['TSFE']->fe_user->user['uid'];
			} elseif (is_object($GLOBALS['BE_USER'])) {
				$addInfo['user_id'] = $GLOBALS['BE_USER']->user['uid'];
			}
		}

		// to be set from outside: $info['user_group']

		if(!$addInfo['page_id'] AND is_object($GLOBALS['TSFE'])) {
			$addInfo['page_id'] = $GLOBALS['TSFE']->id;
		}


		$info = array_merge($fileInfo, $addInfo);

		$time = time();

		$row = array(
			'pid' => tx_dam_db::getPidList(),
			'tstamp' => intval($addInfo['tstamp'])? intval($addInfo['tstamp']) : $time,
			'crdate' => intval($addInfo['crdate'])? intval($addInfo['crdate']) : $time,
			'cruser_id' => intval($addInfo['cruser_id']),

			'file_id' => intval($info['uid']),
			'file_name' => $info['file_name'],
			'file_path' => $info['file_path'],
			'file_type' => $info['file_type'],
			'media_type' => $info['media_type'],
			'file_size' => $info['file_size'],

			'bytes_downloaded' => ($bytes_downloaded===true ? $info['file_size'] : intval($bytes_downloaded)),
			'protected' => $protected ? 1 : 0,

			'host' => $info['host'],
			'user_id' => $info['user_id'],
			'user_name' => $info['user_name'],
			'user_group' => $info['user_group'],
			'page_id' => $info['page_id'],

			'app_id' => $this->app_id,
			'sitetitle' => $this->sitetitle,
			'typo3_mode' => TYPO3_MODE,
		);


		if ($res = $GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_dam_log_download', $row)) {
			$id = $GLOBALS['TYPO3_DB']->sql_insert_id($res);
		}
		return $id;
	}



	/**
	 * Updates a log entry with the bytes downloaded finally
	 * @param integer $id Log id
	 * @param integer $bytes_downloaded Bytes
	 * @return void
	 */
	function update($id, $bytes_downloaded) {
		if ($id=intval($id)) {
			$res = $GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_dam_log_download', 'uid='.intval($id), array('bytes_downloaded' => intval($bytes_downloaded)));
		}
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/naw_securedl/class.tx_nawsecuredl_log.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/naw_securedl/class.tx_nawsecuredl_log.php']);
}
?>