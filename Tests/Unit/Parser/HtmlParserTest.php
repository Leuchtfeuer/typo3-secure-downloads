<?php
namespace Bitmotion\SecureDownloads\Tests\Unit\Parser;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Helmut Hummel (helmut.hummel@typo3.org)
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

class HtmlParserTest extends \Tx_Phpunit_TestCase {
	/**
	 * Enable backup of global and system variables
	 *
	 * @var boolean
	 */
	protected $backupGlobals = TRUE;

	/**
	 * Exclude TYPO3_DB from backup/ restore of $GLOBALS
	 * because resource types cannot be handled during serializing
	 *
	 * @var array
	 */
	protected $backupGlobalsBlacklist = array('TYPO3_DB');

	/**
	 * Data Provider for allConfiguredAssetsAreReplacedInHtml
	 *
	 * @return array
	 */
	public function parseContentTestDataProvider() {
		return array(
			'Image urls are properly replaced' => array('<img src="fileadmin/secure/image.jpg">', '<img src="securedl/fileadmin/secure/image.jpg">'),
			'Link tag urls are properly replaced' => array('<link href="fileadmin/secure/image.jpg">', '<link href="securedl/fileadmin/secure/image.jpg">'),
			'Image urls with strange cases are properly replaced' => array('<img src="fileadmin/secure/image.jPg">', '<img src="securedl/fileadmin/secure/image.jPg">'),
			'XHTML Image urls are properly replaced' => array('<img src="fileadmin/secure/image.jpg" />', '<img src="securedl/fileadmin/secure/image.jpg" />'),
			'Link urls are properly replaced' => array('<a href="fileadmin/secure/image.jpg">', '<a href="securedl/fileadmin/secure/image.jpg">'),
			'Source urls are properly replaced' => array('<source src="fileadmin/secure/image.jpg">', '<source src="securedl/fileadmin/secure/image.jpg">'),
			'Image urls in not secured directories are ignored' => array('<img src="fileadmin/not-secure/image.jpg">', '<img src="fileadmin/not-secure/image.jpg">'),
			'Image urls with not configured types are ignored' => array('<img src="fileadmin/secure/image.gif">', '<img src="fileadmin/secure/image.gif">'),
			'Link urls with not configured types are ignored' => array('<a href="fileadmin/secure/file.doc" target="_blank" title="Click to view the document (PDF, DOC, &hellip;)">', '<a href="fileadmin/secure/file.doc" target="_blank" title="Click to view the document (PDF, DOC, &hellip;)">'),
			'Link urls with configured types are not ignored' => array('<a href="fileadmin/secure/file.pdf" target="_blank" title="Click to view the document (PDF, DOC, &hellip;)">', '<a href="securedl/fileadmin/secure/file.pdf" target="_blank" title="Click to view the document (PDF, DOC, &hellip;)">'),
		);
	}

	/**
	 * @param $originalHtml
	 * @param $expectedHtml
	 * @test
	 * @dataProvider parseContentTestDataProvider
	 */
	public function allConfiguredAssetsAreReplacedInHtml($originalHtml, $expectedHtml) {
		$delegateMock = $this->getMock('Bitmotion\\SecureDownloads\\Parser\\HtmlParserDelegateInterface');
		$delegateMock->expects($this->any())
			->method('publishResourceUri')
			->will($this->returnCallback(function($resourceUri) {return 'securedl/' . $resourceUri;}));
		$settings = array(
			'folderPattern' => 'fileadmin/secure/',
			'domainPattern' => '/',
			'fileExtensionPattern' => 'jpe?g|pdf',
		);
		$fixture = $this->getMock('Bitmotion\\SecureDownloads\\Parser\\HtmlParser', array('dummy'), array($delegateMock, $settings));

		$actualHtml = $fixture->parse($originalHtml);

		$this->assertSame($expectedHtml, $actualHtml);
	}
}

?>