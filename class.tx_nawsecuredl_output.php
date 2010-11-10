<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2005-2007 Dietrich Heise (typo3-ext(at)naw.info)
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
 * @author	Dietrich Heise <typo3-ext(at)naw.info>
 */


// *******************************
// Set error reporting
// *******************************
//error_reporting (E_ALL ^ E_NOTICE);


class tx_nawsecuredl_output {

	protected $arrExtConf = array();

	protected $intFileSize;

	protected $intLogId;

	/**
	 * The init Function, to check the access rights
	 *
	 * @return void
	 */
	function init(){
		$this->arrExtConf = $this->GetExtConf();

		$this->u = intval(t3lib_div::_GP('u'));
		if (!$this->u){
			$this->u = 0;
		}

		$this->hash = t3lib_div::_GP('hash');
		$this->t = t3lib_div::_GP('t');
		$this->file = t3lib_div::_GP('file');

		$this->data = $this->u.$this->file.$this->t;
		$this->checkhash = t3lib_div::hmac($this->data);

		// Hook for init:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']['init'])) {
			$_params = array('pObj' => &$this);
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']['init'] as $_funcRef)   {
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}

		if ($this->checkhash != $this->hash){
			header('HTTP/1.1 403 Forbidden');
			exit ('Access denied!');
		}

		if (intval($this->t) < time()){
			header('HTTP/1.1 403 Forbidden');
			exit ('Access denied!');
		}

		$this->feUserObj = tslib_eidtools::initFeUser();
		tslib_eidtools::connectDB();

		if ($this->u != 0) {
			$feuser = $this->feUserObj->user['uid'];
			if ($this->u != $feuser){
				header('HTTP/1.1 403 Forbidden');
				exit ('Access denied!');
			}
		}
	}


	/**
	 * Output the requested file
	 *
	 * @param data $file
	 */
	public function fileOutput(){

		$file = t3lib_div::getFileAbsFileName($this->removeLeadingSlash($this->file));

		// Hook for pre-output:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']['preOutput'])) {
			$_params = array('pObj' => &$this);
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']['preOutput'] as $_funcRef)   {
				t3lib_div::callUserFunction($_funcRef,$_params,$this);
			}
		}

		if (file_exists($file)) {

			$this->intFileSize = filesize($file);

			$this->logDownload(0);

			// files bigger than 32MB are now 'application/octet-stream' by default (getimagesize memory_limit problem)
			if ($this->intFileSize < 1024*1024*32){
				$bildinfos = @getimagesize($file);
				$bildtypnr = $bildinfos[2];
			}

			$contenttype[1] = 'image/gif';
			$contenttype[2] = 'image/jpeg';
			$contenttype[3] = 'image/png';

			$contenttypedatei = '';
			$contenttypedatei = $contenttype[$bildtypnr];

			if ($contenttypedatei == '') // d.h. wenn noch nicht gesetzt:
			/* try to get the filetype from the fileending */
			{
				$endigung=strtolower(strrchr($file,'.'));
				//alles ab dem letzten Punkt

				if ($this->arrExtConf['forcedownload'] == 1){
					$forcetypes = explode("|",$this->arrExtConf['forcedownloadtype']);
					if (is_array($forcetypes)){
						if (in_array(substr($endigung, 1),$forcetypes)) {
							$forcedownload = true;
						}
					}
				}

				switch(strtolower($endigung)){

					case '.pps':
						$contenttypedatei='application/vnd.ms-powerpoint';
						break;
						##### Microsoft Powerpoint Dateien
					case '.doc':
						$contenttypedatei='application/msword';
						break;
						##### Microsoft Word Dateien
					case '.xls':
						$contenttypedatei='application/vnd.ms-excel';
						break;
						##### Microsoft Excel Dateien

						//TODO: add MS-Office 2007 XML-filetypes

					case '.jpeg':
						$contenttypedatei='image/jpeg';
						break;
						##### JPEG-Dateien
					case '.jpg':
						$contenttypedatei='image/jpeg';
						break;
						##### JPEG-Dateien
					case '.jpe':
						$contenttypedatei='image/jpeg';
						break;
						##### JPEG-Dateien
					case '.mpeg':
						$contenttypedatei='video/mpeg';
						break;
						##### MPEG-Dateien
					case '.mpg':
						$contenttypedatei='video/mpeg';
						break;
						##### MPEG-Dateien
					case '.mpe':
						$contenttypedatei='video/mpeg';
						break;
						##### MPEG-Dateien
					case '.mov':
						$contenttypedatei='video/quicktime';
						break;
						##### Quicktime-Dateien
					case '.avi':
						$contenttypedatei='video/x-msvideo';
						break;
						##### Microsoft AVI-Dateien
					case '.pdf':
						$contenttypedatei='application/pdf';
						break;
					case '.svg':
						$contenttypedatei='image/svg+xml';
						break;
						### Flash Video Files
					case '.flv':
						$contenttypedatei='video/x-flv';
						break;
						### Shockwave / Flash
					case '.swf':
						$contenttypedatei='application/x-shockwave-flash';
						break;
					case '.htm':
					case '.html':
						$contenttypedatei = 'text/html';
						break;
					default:
						$contenttypedatei='application/octet-stream';
						break;
				}//end of switch Case structure
			}

			// Hook for output:
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']['output'])) {
				$_params = array('pObj' => &$this);
				foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']['output'] as $_funcRef)   {
					t3lib_div::callUserFunction($_funcRef,$_params,$this);
				}
			}


			header('Pragma: private');
			header('Expires: 0'); // set expiration time
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Content-Type: '.$contenttypedatei);
			header('Content-Length: '.$this->intFileSize);

			if ($forcedownload == true){
				header('Content-Disposition: attachment; filename="'.basename($file).'"');
			}else{
				header('Content-Disposition: inline; filename="'.basename($file).'"');
			}

			$strOutputFunction = trim($this->arrExtConf['outputFunction']);
			switch ($strOutputFunction) {
				case 'readfile_chunked':
					$this->readfile_chunked($file);
				break;

				case 'fpassthru':
					$handle = fopen($file, 'rb');
					fpassthru($handle);
					fclose($handle);
				break;

				case 'readfile':
					//fallthrough, this is the default case
				default:
					readfile($file);
				break;
			}

			// make sure we can detect an aborted connection, call flush
			ob_flush();
			flush();
			if (!connection_aborted() AND $strOutputFunction !== 'readfile_chunked') {
				$this->logDownload();
			}


		} else {
			print 'File does not exists!';
		}
	}

	/**
	 * Log the access of the file
	 *
	 * @return void
	 */
	protected function logDownload($intFileSize = null)
	{
		if ($this->isLoggingEnabled()) {

			if (is_null($intFileSize)) {
				$intFileSize = $this->intFileSize;
			}

			$data_array = array (
				'tstamp' => time(),
				'file_name' => $this->file,
				'file_size' => $intFileSize,
				'user_id' => intval($this->feUserObj->user['uid']),
			);

			if (is_null($this->intLogId)) {
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_nawsecuredl_counter', $data_array);
				$this->intLogId = intval($GLOBALS['TYPO3_DB']->sql_insert_id());
			} else {
				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('tx_nawsecuredl_counter', '`uid`='.$this->intLogId, $data_array);
			}

		}
	}


	/**
	 * Returns the configuration array
	 *
	 * @return array
	 */
	protected function GetExtConf()
	{
		static $arrExtConf=array();

		if (!$arrExtConf) {
			$arrExtConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['naw_securedl']);
		}

		return $arrExtConf;
	}

	/**
	 * In some cases php needs the filesize as php_memory, so big files cannot
	 * be transferred. This function mitigates this problem.
	 *
	 * @param string $filename
	 * @return bool
	 */
	protected function readfile_chunked($filename)
	{
		$chunksize = intval($this->arrExtConf['outputChunkSize']); // how many bytes per chunk
		$timeout = ini_get('max_execution_time');
		$buffer = '';
		$bytes_sent = 0;
		$handle = fopen($filename, 'rb');
		if ($handle === false) {
			return false;
		}
		while (!feof($handle) && (!connection_aborted()) ) {
			set_time_limit($timeout);
			$buffer = fread($handle, $chunksize);
			print $buffer;
			$bytes_sent += $chunksize;
			ob_flush();
			flush();
			$this->logDownload(t3lib_div::intInRange($bytes_sent, 0, $this->intFileSize));
		}
		return fclose($handle);
	}

	/**
	 * Checks if logging has been enabled in configuration
	 *
	 * @return bool
	 */
	protected function isLoggingEnabled()
	{
		return (bool)$this->arrExtConf['log'];
	}

	/**
	 * Removes a possible leading slash from a string
	 *
	 * @param string
	 * @return string
	 */
	protected function removeLeadingSlash($strValue)
	{
		return preg_replace('/^\//', '', $strValue);
	}

}

$securedl = new tx_nawsecuredl_output();
$securedl->init();
$securedl->fileOutput();

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/naw_securedl/class.tx_nawsecuredl_output.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']);
}
?>