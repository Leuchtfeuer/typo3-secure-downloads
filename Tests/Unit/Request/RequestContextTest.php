<?php
namespace Bitmotion\NawSecuredl\Tests\Unit\Request;

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

class RequestContextTest extends \Tx_Phpunit_TestCase {
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