<?php
namespace Bitmotion\SecureDownloads\Tests\Unit\Resurce\Publishing;

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

class PhpDeliveryProtectedResourcePublishingTargetTest extends \Tx_Phpunit_TestCase {
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
	 * @test
	 */
	public function linkFormatIsSetToDefaultIfNotSetInConfiguration() {
		$fixture = $this->getAccessibleMock('Bitmotion\\SecureDownloads\\Resource\\Publishing\\PhpDeliveryProtectedResourcePublishingTarget', array('dummy'));

		$configurationManagerMock = $this->getMock('Bitmotion\\SecureDownloads\\Configuration\\ConfigurationManager');
		$configurationManagerMock->expects($this->any())
			->method('getValue')
			->with('linkFormat')
			->will($this->returnValue(NULL));
		$fixture->_set('configurationManager', $configurationManagerMock);

		$this->assertSame('index.php?eID=tx_securedownloads&u=999&g=4%2C7%2C8%2C3&t=0&hash=abcdefgh&file=foo', $fixture->_call('buildUri', 'foo', 999, array(4,7,8,3), 0, 'abcdefgh'));
	}


	/**
	 * @test
	 */
	public function linkFormatIsSetToDefaultIfHasOldConfiguration() {
		$fixture = $this->getAccessibleMock('Bitmotion\\SecureDownloads\\Resource\\Publishing\\PhpDeliveryProtectedResourcePublishingTarget', array('dummy'));

		$configurationManagerMock = $this->getMock('Bitmotion\\SecureDownloads\\Configuration\\ConfigurationManager');
		$configurationManagerMock->expects($this->any())
			->method('getValue')
			->with('linkFormat')
			->will($this->returnValue('securedl/###FEUSER###/###TIMEOUT###/###HASH###/###FILE###'));
		$fixture->_set('configurationManager', $configurationManagerMock);

		$this->assertSame('index.php?eID=tx_securedownloads&u=999&g=4%2C7%2C8%2C3&t=0&hash=abcdefgh&file=foo', $fixture->_call('buildUri', 'foo', 999, array(4,7,8,3), 0, 'abcdefgh'));
	}


	/**
	 * @test
	 */
	public function linkFormatIsNotSetToDefaultIfHasNewConfiguration() {
		$fixture = $this->getAccessibleMock('Bitmotion\\SecureDownloads\\Resource\\Publishing\\PhpDeliveryProtectedResourcePublishingTarget', array('dummy'));

		$configurationManagerMock = $this->getMock('Bitmotion\\SecureDownloads\\Configuration\\ConfigurationManager');
		$configurationManagerMock->expects($this->any())
			->method('getValue')
			->with('linkFormat')
			->will($this->returnValue('securedl/###FEUSER###/###FEGROUPS###/###TIMEOUT###/###HASH###/###FILE###'));
		$fixture->_set('configurationManager', $configurationManagerMock);

		$this->assertSame('securedl/999/4%2C7%2C8%2C3/0/abcdefgh/foo', $fixture->_call('buildUri', 'foo', 999, array(4,7,8,3), 0, 'abcdefgh'));
	}
}