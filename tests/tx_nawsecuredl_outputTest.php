<?php

class tx_nawsecuredl_outputTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

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
	 * @var PHPUnit_Framework_MockObject_MockObject|tx_nawsecuredl_output
	 */
	protected $fixture;

	public function setUp() {
		$GLOBALS['naw_securedlTestingContext'] = TRUE;
		require_once(__DIR__ . '/../class.tx_nawsecuredl_output.php');
		$this->fixture = $this->getAccessibleMock('tx_nawsecuredl_output', array('exitScript', 'getExtensionConfiguration', 'hashValid', 'expiryTimeExceeded'));
		$this->fixture->expects($this->any())->method('hashValid')->will($this->returnValue(TRUE));
		$this->fixture->expects($this->any())->method('expiryTimeExceeded')->will($this->returnValue(FALSE));
	}

	public function tearDown() {
			unset($this->fixture);
			unset($GLOBALS['naw_securedlTestingContext']);
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
		$this->fixture = $this->getAccessibleMock('tx_nawsecuredl_output', array('exitScript', 'getExtensionConfiguration', 'hashValid', 'expiryTimeExceeded', 'softQuoteExpression'));
		$this->fixture->_set('extensionConfiguration', array(
			'enableGroupCheck' => '1',
			'groupCheckDirs' => 'foo',
		));
		$this->fixture->expects($this->once())->method('softQuoteExpression');
		$this->fixture->_call('checkGroupAccess');
	}

	public function groupAccessCheckDataProvider() {
		return array(
			'User with exact same groups as transmitted' => array(array(
				'actualGroups' => '1',
				'transmittedGroups' => '1',
				'excludedGroups' => NULL,
				'groupCheckDirs' => NULL,
				'file' => '/foo/bar.php',
				'expected' => TRUE
			)),
			'User with one same group as transmitted' => array(array(
				'actualGroups' => '1,2',
				'transmittedGroups' => '3,1',
				'excludedGroups' => NULL,
				'groupCheckDirs' => NULL,
				'file' => '/foo/bar.php',
				'expected' => TRUE
			)),
			'User with no same group as transmitted' => array(array(
				'actualGroups' => '1,2',
				'transmittedGroups' => '3,4',
				'excludedGroups' => NULL,
				'groupCheckDirs' => NULL,
				'file' => '/foo/bar.php',
				'expected' => FALSE
			)),
			'User with same group which is excluded' => array(array(
				'actualGroups' => '1,2',
				'transmittedGroups' => '3,2',
				'excludedGroups' => '2',
				'groupCheckDirs' => NULL,
				'file' => '/foo/bar.php',
				'expected' => FALSE
			)),
			'User with multiple groups excluded' => array(array(
				'actualGroups' => '1,2,4,5',
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

		$fakeUser = new stdClass();
		$fakeUser->user['usergroup'] = $checkArray['actualGroups'];

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
				'actualGroups' => '1,2',
				'transmittedGroups' => '3,4',
				'excludedGroups' => NULL,
				'groupCheckDirs' => NULL,
				'file' => '/foo/bar.php',
				'expected' => FALSE
			)),
			'User with same group which is excluded' => array(array(
				'actualUser' => '1',
				'transmittedUser' => '4',
				'actualGroups' => '1,2',
				'transmittedGroups' => '3,2',
				'excludedGroups' => '2',
				'groupCheckDirs' => NULL,
				'file' => '/foo/bar.php',
				'expected' => FALSE
			)),
			'User with multiple groups excluded' => array(array(
				'actualUser' => '1',
				'transmittedUser' => '4',
				'actualGroups' => '1,2,4,5',
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
		$this->fixture->_set('extensionConfiguration', array(
			'enableGroupCheck' => '1',
			'groupCheckDirs' => $checkArray['groupCheckDirs'],
			'excludeGroups' => $checkArray['excludedGroups'],
		));

		$fakeUser = new stdClass();
		$fakeUser->user['usergroup'] = $checkArray['actualGroups'];
		$fakeUser->user['uid'] = $checkArray['actualUser'];

		$this->fixture->_set('feUserObj', $fakeUser);
		$this->fixture->_set('file', $checkArray['file']);
		$this->fixture->_set('userGroups', $checkArray['transmittedGroups']);
		$this->fixture->_set('userId', $checkArray['transmittedUser']);
		$_GET['g'] = $checkArray['transmittedGroups'];
		$_GET['u'] = $checkArray['transmittedUser'];

		$this->fixture->expects($this->once())->method('exitScript')->with('Access denied for User!');

		$this->fixture->init();

	}

}

?>