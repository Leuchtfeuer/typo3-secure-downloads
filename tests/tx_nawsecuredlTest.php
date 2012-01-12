<?php

require_once(__DIR__ . '/../class.tx_nawsecuredl.php');

class tx_nawsecuredlTest extends tx_phpunit_testcase {

	/**
	 * @var PHPUnit_Framework_MockObject_MockObject
	 */
	protected $fixture;

	public function setUp() {
		//$this->fixture = $this->getMock('tx_nawsecuredl', array('dummy'));
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function parserIsNotInvokedWhenExtensionIsDisabledByTypoScript() {
		$fakeFrontend = $this->getMock('tslib_fe');
		$fakeFrontend->config['config']['tx_nawsecuredl_enable'] = '0';
		$dummy = NULL;

		$this->fixture = $this->getMock('tx_nawsecuredl', array('parseContent'));
		$this->fixture->expects($this->never())->method('parseContent');
		$this->fixture->parseFE($dummy, $fakeFrontend);
	}

	/**
	 * @test
	 */
	public function parserIsInvokedOnceWhenExtensionIsEnabledByTypoScript() {
		$fakeFrontend = $this->getMock('tslib_fe');
		$fakeFrontend->config['config']['tx_nawsecuredl_enable'] = '1';
		$dummy = NULL;

		$this->fixture = $this->getMock('tx_nawsecuredl', array('parseContent'));
		$this->fixture->expects($this->once())->method('parseContent');
		$this->fixture->parseFE($dummy, $fakeFrontend);
	}

	/**
	 * @test
	 */
	public function parserIsInvokedOnceWhenTypoScriptConfigurationIsNotSet() {
		$fakeFrontend = $this->getMock('tslib_fe');
		$fakeFrontend->config['config'] = array();
		$dummy = NULL;

		$this->fixture = $this->getMock('tx_nawsecuredl', array('parseContent'));
		$this->fixture->expects($this->once())->method('parseContent');
		$this->fixture->parseFE($dummy, $fakeFrontend);
	}

	public function parseContentTestDataProvider() {
		return array(
			'Image urls are properly replaced' => array('<img src="fileadmin/secure/image.jpg">', '<img src="secured/fileadmin/secure/image.jpg">'),
			'Image urls with strange cases are properly replaced' => array('<img src="fileadmin/secure/image.jPg">', '<img src="secured/fileadmin/secure/image.jPg">'),
			'XHTML Image urls are properly replaced' => array('<img src="fileadmin/secure/image.jpg" />', '<img src="secured/fileadmin/secure/image.jpg" />'),
			'Link urls are properly replaced' => array('<a href="fileadmin/secure/image.jpg">', '<a href="secured/fileadmin/secure/image.jpg">'),
			'Source urls are properly replaced' => array('<source src="fileadmin/secure/image.jpg">', '<source src="secured/fileadmin/secure/image.jpg">'),
			'Image urls in not secured directories are ignored' => array('<img src="fileadmin/not-secure/image.jpg">', '<img src="fileadmin/not-secure/image.jpg">'),
			'Image urls with not configured types are ignored' => array('<img src="fileadmin/secure/image.gif">', '<img src="fileadmin/secure/image.gif">'),
			'Link urls with not configured types are ignored' => array('<a href="fileadmin/secure/file.doc" target="_blank" title="Click to view the document (PDF, DOC, &hellip;)">', '<a href="fileadmin/secure/file.doc" target="_blank" title="Click to view the document (PDF, DOC, &hellip;)">'),
			'Link urls with configured types are not ignored' => array('<a href="fileadmin/secure/file.pdf" target="_blank" title="Click to view the document (PDF, DOC, &hellip;)">', '<a href="secured/fileadmin/secure/file.pdf" target="_blank" title="Click to view the document (PDF, DOC, &hellip;)">'),
		);
	}


	public function makeSecureMock($arg) {
		return 'secured/' . $arg;
	}

	/**
	 * @param $originalHtml
	 * @param $expectedHtml
	 * @test
	 * @dataProvider parseContentTestDataProvider
	 */
	public function allConfiguredAssetsAreReplacedInHtml($originalHtml, $expectedHtml) {
		$this->fixture = $this->getMock('tx_nawsecuredl', array('makeSecure', 'getExtensionConfiguration'));

		$this->fixture->expects($this->any())->method('makeSecure')->will($this->returnCallback(array($this, 'makeSecureMock')));
		$this->fixture->expects($this->any())->method('getExtensionConfiguration')->will($this->returnValue(array(
			'securedDirs' => 'fileadmin/secure/',
			'domain' => '/',
			'filetype' => 'jpe?g|pdf'
		)));

		$actualHtml = $this->fixture->parseContent($originalHtml);

		$this->assertSame($expectedHtml, $actualHtml);
	}
}

?>