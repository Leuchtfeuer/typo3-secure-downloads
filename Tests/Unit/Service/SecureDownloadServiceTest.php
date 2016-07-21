<?php
namespace Bitmotion\SecureDownloads\Tests\Unit\Service;

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

class SecureDownloadServiceTest extends \Tx_Phpunit_TestCase {
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
	 * @return \PHPUnit_Framework_MockObject_MockObject|\Bitmotion\SecureDownloads\Request\RequestContext
	 */
	protected function getRequestContextMock() {
		$requestContextMock = $this->getMock('Bitmotion\\SecureDownloads\\Request\\RequestContext');
		$requestContextMock->expects($this->any())->method('isFrontendRequest')->will($this->returnValue(TRUE));
		$requestContextMock->expects($this->any())->method('getCacheLifetime')->will($this->returnValue(0));
		$requestContextMock->expects($this->any())->method('getUserId')->will($this->returnValue(999));
		$requestContextMock->expects($this->any())->method('getUserGroupIds')->will($this->returnValue(array(4,7,8,3)));

		return $requestContextMock;
	}

	/**
	 * @test
	 */
	public function parserIsNotInvokedWhenDisabledInContext() {
		$dummy = array();
		$requestContextMock = $this->getRequestContextMock();
		$requestContextMock->expects($this->any())->method('isUrlRewritingEnabled')->will($this->returnValue(FALSE));
		$fixture = $this->getMock('Bitmotion\\SecureDownloads\\Service\\SecureDownloadService', array('getHtmlParser'), array($requestContextMock));
		$fixture->expects($this->never())->method('getHtmlParser');
		$fixture->parseFE($dummy, $this->getMock('tslib_fe', array(), array(), '', FALSE));
	}

	/**
	 * @test
	 */
	public function parserIsInvokedWhenEnabledInContext() {
		$dummy = array();
		$requestContextMock = $this->getRequestContextMock();
		$requestContextMock->expects($this->any())->method('isUrlRewritingEnabled')->will($this->returnValue(TRUE));
		$htmlParserMock = $this->getMock('Bitmotion\\SecureDownloads\\Parser\\HtmlParser', array(), array(), '', FALSE);
		$htmlParserMock->expects($this->once())->method('parse');
		$fixture = $this->getMock('Bitmotion\\SecureDownloads\\Service\\SecureDownloadService', array('getHtmlParser'), array($requestContextMock));
		$fixture->expects($this->once())->method('getHtmlParser')->will($this->returnValue($htmlParserMock));
		$fixture->parseFE($dummy, $this->getMock('tslib_fe', array(), array(), '', FALSE));
	}
}

?>