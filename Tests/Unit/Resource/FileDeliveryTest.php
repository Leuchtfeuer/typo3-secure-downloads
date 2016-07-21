<?php
namespace Bitmotion\SecureDownloads\Tests\Unit\Resource;

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

class FileDeliveryTest extends \Tx_Phpunit_TestCase {

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
	 * @var \PHPUnit_Framework_MockObject_MockObject|\Bitmotion\SecureDownloads\Resource\FileDelivery
	 */
	protected $fixture;

	public function setUp() {
		$this->fixture = $this->getAccessibleMock('Bitmotion\\SecureDownloads\\Resource\\FileDelivery', array('exitScript', 'getExtensionConfiguration', 'hashValid', 'expiryTimeExceeded', 'initializeUserAuthentication'), array(), '', FALSE);
		$this->fixture->expects($this->any())->method('hashValid')->will($this->returnValue(TRUE));
		$this->fixture->expects($this->any())->method('expiryTimeExceeded')->will($this->returnValue(FALSE));
	}

	public function tearDown() {
			unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function checkGroupAccessReturnsFalseIfGroupAccessIsDisabled(){
		$this->fixture->_set('extensionConfiguration', array(
			'enableGroupCheck' => '0',
		));
		$this->assertSame(FALSE, $this->fixture->_call('checkGroupAccess'));
	}

	/**
	 * @test
	 */
	public function withCheckGroupAccessEnabledDirectoryConfigurationIsChecked(){
		$this->fixture = $this->getAccessibleMock('Bitmotion\\SecureDownloads\\Resource\\FileDelivery', array('exitScript', 'getExtensionConfiguration', 'hashValid', 'expiryTimeExceeded', 'softQuoteExpression', 'initializeUserAuthentication'));
		$this->fixture->_set('extensionConfiguration', array(
			'enableGroupCheck' => '1',
			'groupCheckDirs' => 'foo',
		));
		$fakeUser = new \stdClass();
		$fakeUser->groupData['uid'] = array();
		$this->fixture->_set('feUserObj', $fakeUser);

		$this->fixture->expects($this->once())->method('softQuoteExpression');
		$this->fixture->_call('checkGroupAccess');
	}

	public function groupAccessCheckDataProvider() {
		return array(
			'User with exact same groups as transmitted' => array(array(
				'actualGroups' => array('1'),
				'transmittedGroups' => '1',
				'excludedGroups' => NULL,
				'groupCheckDirs' => NULL,
				'file' => '/foo/bar.php',
				'expected' => TRUE
			)),
			'User with one same group as transmitted' => array(array(
				'actualGroups' => array('1','2'),
				'transmittedGroups' => '3,1',
				'excludedGroups' => NULL,
				'groupCheckDirs' => NULL,
				'file' => '/foo/bar.php',
				'expected' => TRUE
			)),
			'User with no same group as transmitted' => array(array(
				'actualGroups' => array('1','2'),
				'transmittedGroups' => '3,4',
				'excludedGroups' => NULL,
				'groupCheckDirs' => NULL,
				'file' => '/foo/bar.php',
				'expected' => FALSE
			)),
			'User with same group which is excluded' => array(array(
				'actualGroups' => array('1','2'),
				'transmittedGroups' => '3,2',
				'excludedGroups' => '2',
				'groupCheckDirs' => NULL,
				'file' => '/foo/bar.php',
				'expected' => FALSE
			)),
			'User with multiple groups excluded' => array(array(
				'actualGroups' => array('1','2','4','5'),
				'transmittedGroups' => '3,2,4,5',
				'excludedGroups' => '5,2,4',
				'groupCheckDirs' => NULL,
				'file' => '/foo/bar.php',
				'expected' => FALSE
			)),
		);
	}

	/**
	 * @dataProvider groupAccessCheckDataProvider
	 * @param $checkArray
	 * @test
	 */
	public function groupAccessChecksWorksAsExpected($checkArray) {
		$this->fixture->_set('extensionConfiguration', array(
			'enableGroupCheck' => '1',
			'groupCheckDirs' => $checkArray['groupCheckDirs'],
			'excludeGroups' => $checkArray['excludedGroups'],
		));

		$fakeUser = new \stdClass();
		$fakeUser->groupData['uid'] = $checkArray['actualGroups'];

		$this->fixture->_set('feUserObj', $fakeUser);
		$this->fixture->_set('file', $checkArray['file']);
		$this->fixture->_set('userGroups', $checkArray['transmittedGroups']);

		$this->assertSame($checkArray['expected'], $this->fixture->_call('checkGroupAccess'));
	}

	public function accessCheckDataProvider() {
		return array(
			'User with no same group as transmitted' => array(array(
				'actualUser' => '1',
				'transmittedUser' => '4',
				'actualGroups' => array('1','2'),
				'transmittedGroups' => '3,4',
				'excludedGroups' => NULL,
				'groupCheckDirs' => NULL,
				'file' => '/foo/bar.php',
				'expected' => FALSE
			)),
			'User with same group which is excluded' => array(array(
				'actualUser' => '1',
				'transmittedUser' => '4',
				'actualGroups' => array('1','2'),
				'transmittedGroups' => '3,2',
				'excludedGroups' => '2',
				'groupCheckDirs' => NULL,
				'file' => '/foo/bar.php',
				'expected' => FALSE
			)),
			'User with multiple groups excluded' => array(array(
				'actualUser' => '1',
				'transmittedUser' => '4',
				'actualGroups' => array('1','2','4','5'),
				'transmittedGroups' => '3,2,4,5',
				'excludedGroups' => '5,2,4',
				'groupCheckDirs' => NULL,
				'file' => '/foo/bar.php',
				'expected' => FALSE
			)),
		);
	}

	/**
	 * @dataProvider accessCheckDataProvider
	 * @param $checkArray
	 * @test
	 */
	public function accessChecksWorksAsExpected($checkArray) {
		$this->fixture->expects($this->any())->method('getExtensionConfiguration')->will($this->returnValue(array(
			'enableGroupCheck' => '1',
			'groupCheckDirs' => $checkArray['groupCheckDirs'],
			'excludeGroups' => $checkArray['excludedGroups'],
		)));

		$fakeUser = new \stdClass();
		$fakeUser->groupData['uid'] = $checkArray['actualGroups'];
		$fakeUser->user['uid'] = $checkArray['actualUser'];

		$this->fixture->_set('feUserObj', $fakeUser);
		$this->fixture->_set('file', $checkArray['file']);
		$this->fixture->_set('userGroups', $checkArray['transmittedGroups']);
		$this->fixture->_set('userId', $checkArray['transmittedUser']);
		$_GET['g'] = $checkArray['transmittedGroups'];
		$_GET['u'] = $checkArray['transmittedUser'];

		$this->fixture->expects($this->once())->method('exitScript')->with('Access denied for User!');

		$this->fixture->__construct();

	}

}

?>