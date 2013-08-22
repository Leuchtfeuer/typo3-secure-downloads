<?php
/**
 * Created by JetBrains PhpStorm.
 * User: helmut
 * Date: 22.08.13
 * Time: 17:39
 * To change this template use File | Settings | File Templates.
 */

class RequestContextTest extends Tx_Phpunit_TestCase {
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
	public function isRewritingEnabledWillReturnTrueByDefault() {
		$fakeFrontend = $this->getMock('tslib_fe', array(), array(), '', FALSE);
		$fakeFrontend->config['config'] = array();
		$GLOBALS['TSFE'] = $fakeFrontend;

		$fixture = $this->getAccessibleMock('Bitmotion\\NawSecuredl\\Request\\RequestContext', array('isFrontendRequest'), array(), '', FALSE);
		$fixture->expects($this->any())->method('isFrontendRequest')->will($this->returnValue(TRUE));
		$fixture->__construct();

		$this->assertTrue($fixture->isUrlRewritingEnabled());
	}

	/**
	 * @test
	 */
	public function isRewritingEnabledWillReturnTrueWhenExplicitlyEnabled() {
		$fakeFrontend = $this->getMock('tslib_fe', array(), array(), '', FALSE);
		$fakeFrontend->config['config']['tx_nawsecuredl_enable'] = '1';
		$GLOBALS['TSFE'] = $fakeFrontend;

		$fixture = $this->getAccessibleMock('Bitmotion\\NawSecuredl\\Request\\RequestContext', array('isFrontendRequest'), array(), '', FALSE);
		$fixture->expects($this->any())->method('isFrontendRequest')->will($this->returnValue(TRUE));
		$fixture->__construct();

		$this->assertTrue($fixture->isUrlRewritingEnabled());
	}

	/**
	 * @test
	 */
	public function isRewritingEnabledWillReturnFalseWhenExplicitlyDisabled() {
		$fakeFrontend = $this->getMock('tslib_fe', array(), array(), '', FALSE);
		$fakeFrontend->config['config']['tx_nawsecuredl_enable'] = '0';
		$GLOBALS['TSFE'] = $fakeFrontend;

		$fixture = $this->getAccessibleMock('Bitmotion\\NawSecuredl\\Request\\RequestContext', array('isFrontendRequest'), array(), '', FALSE);
		$fixture->expects($this->any())->method('isFrontendRequest')->will($this->returnValue(TRUE));
		$fixture->__construct();

		$this->assertFalse($fixture->isUrlRewritingEnabled());
	}
}