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
 * @author	Helmut Hummel <typo3-ext(at)naw.info>
 */
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

		$this->data = $this->u . $this->file . $this->t;
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

		$file = t3lib_div::getFileAbsFileName(ltrim($this->file, '/'));
		$fileName = basename($file);
			// This is a workaround for a PHP bug on Windows systems:
			// @see http://bugs.php.net/bug.php?id=46990
			// It helps for filenames with special characters that are present in latin1 encoding.
			// If you have real UTF-8 filenames, use a nix based OS.
		if (TYPO3_OS == 'WIN') {
			$file = utf8_decode($file);
		}


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

			if ($contenttypedatei === '') {
				$endigung = $this->getFileExtensionByFilename($file);

				if ((bool)$this->arrExtConf['forcedownload'] === TRUE){
					$forcetypes = t3lib_div::trimExplode("|", $this->arrExtConf['forcedownloadtype']);
					if (is_array($forcetypes)){
						if (in_array($endigung, $forcetypes)) {
							$forcedownload = true;
						}
					}
				}

				$contenttypedatei = $this->getMimeTypeByFileExtension($endigung);
			}

			// Hook for output:
			// TODO: deprecate this hook?
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']['output'])) {
				$_params = array(
					'pObj' => &$this,
					'fileExtension' => '.' . $endigung, // Add leading dot for compatibility in this hook
					'mimeType' => &$contenttypedatei,
				);
				foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']['output'] as $_funcRef)   {
					t3lib_div::callUserFunction($_funcRef, $_params, $this);
				}
			}

			header('Pragma: private');
			header('Expires: 0'); // set expiration time
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Content-Type: '.$contenttypedatei);
			header('Content-Length: '.$this->intFileSize);

			if ($forcedownload == true){
				header('Content-Disposition: attachment; filename="' . $fileName .'"');
			}else{
				header('Content-Disposition: inline; filename="' . $fileName .'"');
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
	 * @param string $strFileName
	 * @return bool
	 */
	protected function readfile_chunked($strFileName)
	{
		$chunksize = intval($this->arrExtConf['outputChunkSize']); // how many bytes per chunk
		$timeout = ini_get('max_execution_time');
		$buffer = '';
		$bytes_sent = 0;
		$handle = fopen($strFileName, 'rb');
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
	 * Extracts the file extension out of a complete file name.
	 *
	 * @param string $strFileName
	 */
	protected function getFileExtensionByFilename($strFileName)
	{
		return t3lib_div::strtolower(ltrim(strrchr($strFileName, '.'), '.'));
	}

	/**
	 * Looks up the mime type for a give file extension
	 *
	 * @param string $strFileExtension lowercase file extension
	 * @return string mime type
	 */
	protected function getMimeTypeByFileExtension($strFileExtension)
	{
		$strMimeTypesArray = array(


		);

			// Read all additional MIME types from the EM configuration into the array $strAdditionalMimeTypesArray
		if ($this->arrExtConf['additionalMimeTypes']) {
				// Array with key/value pairs consisting of file extension (with dot in front) and mime type
			$strAdditionalMimeTypesArray = array();
			$strAdditionalFileExtension = '';
			$strAdditionalMimeType = '';

			$strAdditionalMimeTypePartsArray = t3lib_div::trimExplode(',', $this->arrExtConf['additionalMimeTypes'], TRUE);

			foreach($strAdditionalMimeTypePartsArray as $strAdditionalMimeTypeItem) {
				list($strAdditionalFileExtension, $strAdditionalMimeType) = t3lib_div::trimExplode('|', $strAdditionalMimeTypeItem);
				if(!empty($strAdditionalFileExtension) && !empty($strAdditionalMimeType)) {
					$strAdditionalFileExtension = t3lib_div::strtolower($strAdditionalFileExtension);
					$strAdditionalMimeTypesArray[$strAdditionalFileExtension] = $strAdditionalMimeType;
				}
			}

			unset($strAdditionalFileExtension);
			unset($strAdditionalMimeType);
		}

		//TODO: Add hook to be able to manipulate and/or add mime types

			// Check if an specific MIME type is configured for this file extension
		if ($strAdditionalMimeTypesArray[$strFileExtension]) {
			$strMimeType = $strAdditionalMimeTypesArray[$strFileExtension];
		} else {
			switch($strFileExtension){
					// MS-Office filetypes
				case '.pps':
					$strMimeType = 'application/vnd.ms-powerpoint';
					break;
				case '.doc':
					$strMimeType = 'application/msword';
					break;
				case '.xls':
					$strMimeType = 'application/vnd.ms-excel';
					break;
				case '.docm':
					$strMimeType = 'application/vnd.ms-word.document.macroEnabled.12';
					break;
				case '.docx':
					$strMimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
					break;
				case '.dotm':
					$strMimeType = 'application/vnd.ms-word.template.macroEnabled.12';
					break;
				case '.dotx':
					$strMimeType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.template';
					break;
				case '.ppsm':
					$strMimeType = 'application/vnd.ms-powerpoint.slideshow.macroEnabled.12';
					break;
				case '.ppsx':
					$strMimeType = 'application/vnd.openxmlformats-officedocument.presentationml.slideshow';
					break;
				case '.pptm':
					$strMimeType = 'application/vnd.ms-powerpoint.presentation.macroEnabled.12';
					break;
				case '.pptx':
					$strMimeType = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
					break;
				case '.xlsb':
					$strMimeType = 'application/vnd.ms-excel.sheet.binary.macroEnabled.12';
					break;
				case '.xlsm':
					$strMimeType = 'application/vnd.ms-excel.sheet.macroEnabled.12';
					break;
				case '.xlsx':
					$strMimeType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
					break;
				case '.xps':
					$strMimeType = 'application/vnd.ms-xpsdocument';
					break;

					// Open-Office filetypes
				case '.odt':
					$strMimeType = 'application/vnd.oasis.opendocument.text';
					break;
				case '.ott':
					$strMimeType = 'application/vnd.oasis.opendocument.text-template';
					break;
				case '.odg':
					$strMimeType = 'application/vnd.oasis.opendocument.graphics';
					break;
				case '.otg':
					$strMimeType = 'application/vnd.oasis.opendocument.graphics-template';
					break;
				case '.odp': $strMimeType = 'application/vnd.oasis.opendocument.presentation';
					break;
				case '.otp':
					$strMimeType = 'application/vnd.oasis.opendocument.presentation-template';
					break;
				case '.ods':
					$strMimeType = 'application/vnd.oasis.opendocument.spreadsheet';
					break;
				case '.ots':
					$strMimeType = 'application/vnd.oasis.opendocument.spreadsheet-template';
					break;
				case '.odc':
					$strMimeType = 'application/vnd.oasis.opendocument.chart';
					break;
				case '.otc':
					$strMimeType = 'application/vnd.oasis.opendocument.chart-template';
					break;
				case '.odi':
					$strMimeType = 'application/vnd.oasis.opendocument.image';
					break;
				case '.oti':
					$strMimeType = 'application/vnd.oasis.opendocument.image-template';
					break;
				case '.odf':
					$strMimeType = 'application/vnd.oasis.opendocument.formula';
					break;
				case '.otf':
					$strMimeType = 'application/vnd.oasis.opendocument.formula-template';
					break;
				case '.odm':
					$strMimeType = 'application/vnd.oasis.opendocument.text-master';
					break;
				case '.oth':
					$strMimeType = 'application/vnd.oasis.opendocument.text-web';
					break;

					// Media file types
				case '.jpeg':
					$strMimeType = 'image/jpeg';
					break;
					##### JPEG-Dateien
				case '.jpg':
					$strMimeType = 'image/jpeg';
					break;
					##### JPEG-Dateien
				case '.jpe':
					$strMimeType = 'image/jpeg';
					break;
					##### JPEG-Dateien
				case '.mpeg':
					$strMimeType = 'video/mpeg';
					break;
					##### MPEG-Dateien
				case '.mpg':
					$strMimeType = 'video/mpeg';
					break;
					##### MPEG-Dateien
				case '.mpe':
					$strMimeType = 'video/mpeg';
					break;
					##### MPEG-Dateien
				case '.mov':
					$strMimeType = 'video/quicktime';
					break;
					##### Quicktime-Dateien
				case '.avi':
					$strMimeType = 'video/x-msvideo';
					break;
					##### Microsoft AVI-Dateien
				case '.pdf':
					$strMimeType = 'application/pdf';
					break;
				case '.svg':
					$strMimeType = 'image/svg+xml';
					break;
					### Flash Video Files
				case '.flv':
					$strMimeType = 'video/x-flv';
					break;
					### Shockwave / Flash
				case '.swf':
					$strMimeType = 'application/x-shockwave-flash';
					break;
				case '.htm':
				case '.html':
					$contenttypedatei = 'text/html';
					break;
				default:
					$strMimeType = 'application/octet-stream';
					break;
			}//end of switch Case structure
		}
		return $strMimeType;
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
}

$securedl = t3lib_div::makeInstance('tx_nawsecuredl_output');
$securedl->init();
$securedl->fileOutput();

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/naw_securedl/class.tx_nawsecuredl_output.php'])	{
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/naw_securedl/class.tx_nawsecuredl_output.php']);
}
?>