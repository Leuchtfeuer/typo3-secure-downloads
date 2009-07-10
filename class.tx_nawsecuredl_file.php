<?php
/**
 * Naw Secure Download
 *
 * LICENSE
 *
 * This source file is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License version 2 as
 * published by the Free Software Foundation.
 *
 * This script is distributed in the hope that it will be useful, but
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General
 * Public License for more details.
 *
 * @category   TYPO3
 * @package    naw_securedl
 * @copyright  Copyright (c) 2008-2009 naw.info
 * @license    http://www.gnu.org/licenses/gpl-2.0.html     GNU General Public License, version 2
 * @version    $Id$
 */

/**
 * File object which is a variant of tx_next_DataRow_txdam which works wit files instead of DAM
 * This culd also the base class for File classes which get some data like caption from the database from some special table
 *
 * STATUS alpha -
 *
 * @author Rene Fritz (typo3-ext@naw.info)
 * @category   TYPO3
 * @package    naw_securedl
 *
 * @property-read string $Hash md5 file hash
 * @property-read integer $Status TXDAM_status_file_ok, TXDAM_status_file_missing
 * @property-read string $Name The file name
 * @property-read string $DownloadName The name which should be used for downloads
 * @property-read string $Title The title which is NOT the file name
 * @property-read string $AbsolutePath
 * @property-read string $Path This is the default path to be used in most applications which is normally the relative path
 * @property-read string $RelativePath
 * @property-read integer $Mtime
 * @property-read integer $Tstamp
 * @property-read integer $Ctime
 * @property-read integer $Crdate
 * @property-read integer $Inode
 * @property-read integer $Size
 * @property-read string $Owner
 * @property-read string $Perms
 * @property-read boolean $isWritable
 * @property-read boolean $isReadable
 * @property-read integer $Hidden For records and DAM compatibility
 * @property-read integer $Deleted For records and DAM compatibility
 * @property-read string $MimeType a mime content type like: 'image/jpeg'
 * @property-read string $MimeBasetype a mime base content type like: 'image'
 * @property-read string $MimeSubtype a mime sub content type like: 'jpeg'
 * @property-read integer $MediaType see tx_next_SystemFiles::ConvertMediaType()
 * @property-read string $Type the file type like mp3, txt, pdf.
 * @property-read string $Suffix is in most cases the same as $Type
 * @property array $DataArray meta data for the file with key=>$value pairs
 * @property object $DataObject meta data object for the file
 */
class tx_nawsecuredl_file {


	/**
	 * Absolute path to the file
	 * @var string
	 */
	protected $strAbsFilePath;

	/**
	 * cached splitted file path
	 * @var string
	 */
	protected $strPathPartsArray = array();

	/**
	 * cached content of pseudo fields
	 * @var array
	 */
	protected $strDataCacheArray = array();

	/**
	 * additional meta data (eg. from the database)
	 *
	 * @var array
	 */
	protected $_extraData = null;




	/**
	 * @param string $filename file path (absolute or relative)
	 * @param array|object $metaData some extra data which can be used for the file
	 */
	public function __construct($filename, $metaData=null) {

//		$this->strAbsFilePath = t3lib_div::getFileAbsFileName($filename, false);
		$this->strAbsFilePath = $filename;
		// doesn't handle utf8 in all php versions: $this->strPathPartsArray = pathinfo($fileInfo);
		$this->strPathPartsArray = self::getFilenameParts($this->strAbsFilePath);

		if ($metaData)
			$this->_extraData = (is_array($metaData) ? new ArrayObject($metaData, ArrayObject::ARRAY_AS_PROPS) : $metaData);

	}



	/*************************
	 *
	 * Get/Set methods
	 *
	 *************************/


	/**
	 * Override method to perform a property "Set"
	 * This will set the property $strName to be $mixValue
	 *
	 * @param string $strName Name of the property to set
	 * @param string $mixValue New value of the property
	 * @return mixed
	 */
	public function __set($strName, $mixValue)
	{
		switch ($strName) {

			case 'DataArray':
			case 'DataObject':
					$this->_extraData = (is_array($mixValue) ? new ArrayObject($mixValue, ArrayObject::ARRAY_AS_PROPS) : $mixValue);
					return $this->_extraData;
				break;
			default:
				return  ($this->_extraData->$strName = $mixValue);

				throw new Exception('Column "'.$strName.'" does not exist!');

		}
	}


	/**
	 * Override method to perform a property "Get"
	 * This will get the property $strName
	 *
	 * @param string $strName Name of the property to get
	 * @return mixed
	 */
	public function __get($strName)
	{
		// change 'file_name' to 'name'
		$strName = str_replace('file_', '', strtolower($strName));

		// use computed value when available
		if (array_key_exists($strName, $this->strDataCacheArray)) {
			return $this->strDataCacheArray[$strName];
		}

		switch ($strName) {
			case 'tablename':
					return null;
				break;

			case 'dataarray':
					return (array)$this->_extraData;
			case 'dataobject':
					return $this->_extraData;
				break;
			case 'dataraw':
					return $this;
				break;
			case 'datafields':
					return array_keys ((array)$this->_extraData);
				break;

			case 'hash':

				$filename = $this->strAbsFilePath;
				if (function_exists('md5_file')) {
					$hash = @md5_file($filename); /*@*/
				} else {
					if(filesize ($filename) > 0xfffff ) {	// 1MB
						$cmd = t3lib_exec::getCommand('md5sum');
						$output = array();
						$retval = 0;
						exec($cmd.' -b '.escapeshellcmd($filename), $output, $retval);
						$output = explode(' ',$output[0]);
						$match = array();
						if (preg_match('#[0-9a-f]{32}#', $output[0], $match)) {
							$hash = $match[0];
						}
					} else {
						$file_string = t3lib_div::getUrl($filename);
						$hash = md5($file_string);
					}
				}

				return $this->strDataCacheArray[$strName] = $hash;


			case 'status':
				return (@is_file($this->strAbsFilePath) ? TXDAM_status_file_ok : TXDAM_status_file_missing); /*@*/

			case 'name':
			case 'downloadname':
				return $this->strPathPartsArray['file'];

			case 'title':
				return $this->strDataCacheArray[$strName] = tx_next_SystemFiles::MakeTitleFromFilename ($this->strPathPartsArray['file']);

			case 'absolutepath':
				echo"Hallo";
				return $this->strPathPartsArray['path'];

			case 'path':
			case 'relativePath':
				return self::path_makeRelative ($this->strPathPartsArray['path']);

			case 'mtime':
			case 'tstamp':
				return $this->strDataCacheArray[$strName] = @filemtime($this->strAbsFilePath); /*@*/

			case 'ctime':
			case 'crdate':
				return $this->strDataCacheArray[$strName] = @filectime($this->strAbsFilePath);

			case 'inode':
				return $this->strDataCacheArray[$strName] = @fileinode($this->strAbsFilePath);

			case 'size':
				return $this->strDataCacheArray[$strName] = @filesize($this->strAbsFilePath);

			case 'owner':
				return $this->strDataCacheArray[$strName] = @fileowner($this->strAbsFilePath);

			case 'perms':
				return $this->strDataCacheArray[$strName] = @fileperms($this->strAbsFilePath);

			case 'iswritable':
			case 'writable':
				return $this->strDataCacheArray[$strName] = @is_writable($this->strAbsFilePath);

			case 'isreadable':
			case 'readable':
				return $this->strDataCacheArray[$strName] = @is_readable($this->strAbsFilePath);

			case 'hidden':
			case 'deleted':
				return $this->strDataCacheArray[$strName] = @is_file($this->strAbsFilePath);

			case 'mimetype':
			case 'mimebasetype':
			case 'mimesubtype':
			case 'mediatype':
			case 'type':
			case 'extension':
			case 'suffix':

				$mediaTypeArray = tx_next_SystemFiles::DetectFileMimeType($this->strAbsFilePath);

				$this->strDataCacheArray['mimetype'] =     $mediaTypeArray['mime_type'];
				$this->strDataCacheArray['mimebasetype'] = $mediaTypeArray['mime_basetype'];
				$this->strDataCacheArray['mimesubtype'] =  $mediaTypeArray['mime_subtype'];
				$this->strDataCacheArray['mediatype'] =    $mediaTypeArray['media_type'];
				$this->strDataCacheArray['type'] =         $mediaTypeArray['file_type'];
				$this->strDataCacheArray['extension'] =    $mediaTypeArray['file_type'];
				$this->strDataCacheArray['suffix'] =       $mediaTypeArray['file_type'];

				return $this->strDataCacheArray[$strName];


			default:

				return  $this->_extraData->$strName;

				throw new Exception('Column "'.$strName.'" does not exist!');
			break;
		}
	}


	/**
	 * Splits a reference to a file in 5 parts
	 *
	 * @param	string		Filename/filepath to be analysed
	 * @return	array		Contains keys [path], [file], [filebody], [fileext], [realFileext]
	 */
	public static function getFilenameParts($fileref)
	{
		$reg = array();

		$info['path'] = dirname($fileref);
		$info['file'] = basename($fileref);

		$match = array();
		if (preg_match('/(.*)\.([^\.]*$)/',$info['file'],$match)) {
			$info['filebody'] = $match[1];
			$info['fileext'] = strtolower($match[2]);
			$info['realFileext'] = $match[2];
		} else {
			$info['filebody'] = $info['file'];
			$info['fileext'] = '';
		}
		reset($info);
		return $info;

	}


	/**
	 * Allows for the enabling of DB profiling while in middle of the script
	 *
	 * @return void
	 */
	public function FieldExists($strName)
	{
		try {
			$dummy = $this->$strName;
			return true;
		} catch ( Exception $e ) {
			// nothing to do
		}
		return false;
	}




	/*************************
	 *
	 * File methods
	 *
	 *************************/



	/**
	 * Check if file exists
	 *
	 * @return boolean
	 */
	public function Exists()
	{
		return @is_file($this->strAbsFilePath);
	}


	/**
	 * Returns the ID which is the file path here
	 *
	 * @return	integer
	 */
	public function GetID ()
	{
		return $this->strAbsFilePath;
	}


	/**
	 * Returns a hash to identify the file. Searching for a file using this hash can be done with DAM only.
	 *
	 * @return	string		hash
	 */
	public function GetHash ()
	{
		return $this->Hash;
	}


	/**
	 * Returns the file type like mp3, txt, pdf.
	 *
	 * @return	string		The file type like mp3, txt, pdf.
	 */
	public function GetType ()
	{
		return $this->Type;
	}


	/**
	 * Returns a mime content type like: 'image/jpeg'
	 *
	 * @return	string eg. 'image/jpeg'
	 */
	public function GetMimeType ()
	{
		return $this->MimeType;
	}


	/**
	 * Returns the download name for the file.
	 * This don't have to be the real file name. For usage with "Content-Disposition" HTTP header.
	 * header("Content-Disposition: attachment; filename=$downloadFilename");
	 *
	 * @return	string		File name for download.
	 */
	public function GetDownloadName ()
	{
		return $this->DownloadName;
	}


	/**
	 * Returns a file path relative to PATH_site or getIndpEnv('TYPO3_SITE_URL').
	 *
	 * @return	string		Relative path to file
	 */
	public function GetPathWebRelative ()
	{
		return tx_next_SystemFiles::MakeFilePathWebRelative($this->strAbsFilePath);
	}


	/**
	 * Returns an absolute file path
	 *
	 * @return	string		Absolute path to file
	 */
	public function GetPathAbsolute ()
	{
		return $this->strAbsFilePath;
	}


	/**
	 * Returns an absolute file path
	 *
	 * @return	string	The file size in a formatted way like 45 kb
	 */
	public function GetSizeFormatted ()
	{
		return tx_next_Output::FormatFileSize(intval($this->Size));
	}

}









