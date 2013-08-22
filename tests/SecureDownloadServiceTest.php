<?php

require_once( __DIR__ . '/../Classes/Service/SecureDownloadService.php' );

class SecureDownloadServiceTest extends Tx_Phpunit_TestCase {
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
	 * @var \PHPUnit_Framework_MockObject_MockObject|\Bitmotion\NawSecuredl\Service\SecureDownloadService
	 */
	protected $fixture;

	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject|\tslib_fe
	 */
	protected $fakeFrontend;

	public function setUp() {
		$this->fakeFrontend = $this->getMock('tslib_fe', array(), array(), '', FALSE);
		$this->fakeFrontend->page['cache_timeout'] = '0';
		$this->fakeFrontend->fe_user->user['uid'] = '999';
		$this->fakeFrontend->fe_user->user['usergroup'] = '4,7,8,3';
		$GLOBALS['TSFE'] = $this->fakeFrontend;
		$GLOBALS['EXEC_TIME'] = 0;
	}

	public function tearDown() {
		unset($this->fixture);
		unset($this->fakeFrontend);
	}

	/**
	 * @return PHPUnit_Framework_MockObject_MockObject|\Bitmotion\NawSecuredl\Request\RequestContext
	 */
	protected function getRequestContextMock() {
		$requestContextMock = $this->getMock('Bitmotion\\NawSecuredl\\Request\\RequestContext');
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
		$dummy = NULL;
		$requestContextMock = $this->getRequestContextMock();
		$requestContextMock->expects($this->any())->method('isUrlRewritingEnabled')->will($this->returnValue(FALSE));
		$this->fixture = $this->getMock('Bitmotion\\NawSecuredl\\Service\\SecureDownloadService', array('parseContent'), array($requestContextMock));
		$this->fixture->expects($this->never())->method('parseContent');
		$this->fixture->parseFE($dummy, $this->fakeFrontend);
	}

	/**
	 * @test
	 */
	public function parserIsNotInvokedWhenEnabledInContext() {
		$dummy = NULL;
		$requestContextMock = $this->getRequestContextMock();
		$requestContextMock->expects($this->any())->method('isUrlRewritingEnabled')->will($this->returnValue(TRUE));

		$this->fixture = $this->getMock('Bitmotion\\NawSecuredl\\Service\\SecureDownloadService', array('parseContent'), array($requestContextMock));
		$this->fixture->expects($this->once())->method('parseContent');
		$this->fixture->parseFE($dummy, $this->fakeFrontend);
	}

	/**
	 * @test
	 */
	public function linkFormatIsSetToDefaultIfNotSetInConfiguration() {
		$this->fixture = $this->getMock('Bitmotion\\NawSecuredl\\Service\\SecureDownloadService', array('getHash', 'getExtensionConfiguration'));

		$this->fixture->expects($this->any())->method('getHash')->will($this->returnValue('abcdefgh'));
		$this->fixture->expects($this->any())->method('getExtensionConfiguration')->will($this->returnValue(array(
			'securedDirs' => 'fileadmin/secure/',
			'domain' => '/',
			'filetype' => 'jpe?g|pdf',
			'cachetimeadd' => 0,
			'linkFormat' => NULL,
		)));

		$this->fixture->__construct($this->getRequestContextMock());
		$this->assertSame('/index.php?eID=tx_nawsecuredl&u=999&g=4%2C7%2C8%2C3&t=86400&hash=abcdefgh&file=foo', $this->fixture->makeSecure('foo'));
	}


	/**
	 * @test
	 */
	public function linkFormatIsSetToDefaultIfHasOldConfiguration() {
		$this->fixture = $this->getMock('Bitmotion\\NawSecuredl\\Service\\SecureDownloadService', array('getHash', 'getExtensionConfiguration'), array($this->getRequestContextMock()));

		$this->fixture->expects($this->any())->method('getHash')->will($this->returnValue('abcdefgh'));
		$this->fixture->expects($this->any())->method('getExtensionConfiguration')->will($this->returnValue(array(
			'securedDirs' => 'fileadmin/secure/',
			'domain' => '/',
			'filetype' => 'jpe?g|pdf',
			'cachetimeadd' => 0,
			'linkFormat' => 'securedl/###FEUSER###/###TIMEOUT###/###HASH###/###FILE###',
		)));

		$this->fixture->__construct($this->getRequestContextMock());
		$this->assertSame('/index.php?eID=tx_nawsecuredl&u=999&g=4%2C7%2C8%2C3&t=86400&hash=abcdefgh&file=foo', $this->fixture->makeSecure('foo'));
	}


	/**
	 * @test
	 */
	public function linkFormatIsNotSetToDefaultIfHasNewConfiguration() {
		$this->fixture = $this->getMock('Bitmotion\\NawSecuredl\\Service\\SecureDownloadService', array('getHash', 'getExtensionConfiguration'), array($this->getRequestContextMock()));

		$this->fixture->expects($this->any())->method('getHash')->will($this->returnValue('abcdefgh'));
		$this->fixture->expects($this->any())->method('getExtensionConfiguration')->will($this->returnValue(array(
			'securedDirs' => 'fileadmin/secure/',
			'domain' => '/',
			'filetype' => 'jpe?g|pdf',
			'cachetimeadd' => 0,
			'linkFormat' => 'securedl/###FEUSER###/###FEGROUPS###/###TIMEOUT###/###HASH###/###FILE###',
		)));

		$this->fixture->__construct($this->getRequestContextMock());
		$this->fixture->makeSecure('foo');

		$this->assertSame('securedl/999/4%2C7%2C8%2C3/86400/abcdefgh/foo', $this->fixture->makeSecure('foo'));

	}


	/**
	 * Data Provider for allConfiguredAssetsAreReplacedInHtml
	 *
	 * @return array
	 */
	public function parseContentTestDataProvider() {
		return array(
			'Image urls are properly replaced' => array('<img src="fileadmin/secure/image.jpg">', '<img src="securedl/999/4%2C7%2C8%2C3/86400/abcdefgh/fileadmin/secure/image.jpg">'),
			'Image urls with strange cases are properly replaced' => array('<img src="fileadmin/secure/image.jPg">', '<img src="securedl/999/4%2C7%2C8%2C3/86400/abcdefgh/fileadmin/secure/image.jPg">'),
			'XHTML Image urls are properly replaced' => array('<img src="fileadmin/secure/image.jpg" />', '<img src="securedl/999/4%2C7%2C8%2C3/86400/abcdefgh/fileadmin/secure/image.jpg" />'),
			'Link urls are properly replaced' => array('<a href="fileadmin/secure/image.jpg">', '<a href="securedl/999/4%2C7%2C8%2C3/86400/abcdefgh/fileadmin/secure/image.jpg">'),
			'Source urls are properly replaced' => array('<source src="fileadmin/secure/image.jpg">', '<source src="securedl/999/4%2C7%2C8%2C3/86400/abcdefgh/fileadmin/secure/image.jpg">'),
			'Image urls in not secured directories are ignored' => array('<img src="fileadmin/not-secure/image.jpg">', '<img src="fileadmin/not-secure/image.jpg">'),
			'Image urls with not configured types are ignored' => array('<img src="fileadmin/secure/image.gif">', '<img src="fileadmin/secure/image.gif">'),
			'Link urls with not configured types are ignored' => array('<a href="fileadmin/secure/file.doc" target="_blank" title="Click to view the document (PDF, DOC, &hellip;)">', '<a href="fileadmin/secure/file.doc" target="_blank" title="Click to view the document (PDF, DOC, &hellip;)">'),
			'Link urls with configured types are not ignored' => array('<a href="fileadmin/secure/file.pdf" target="_blank" title="Click to view the document (PDF, DOC, &hellip;)">', '<a href="securedl/999/4%2C7%2C8%2C3/86400/abcdefgh/fileadmin/secure/file.pdf" target="_blank" title="Click to view the document (PDF, DOC, &hellip;)">'),
		);
	}

	/**
	 * @param $originalHtml
	 * @param $expectedHtml
	 * @test
	 * @dataProvider parseContentTestDataProvider
	 */
	public function allConfiguredAssetsAreReplacedInHtml($originalHtml, $expectedHtml) {
		$this->fixture = $this->getMock('Bitmotion\\NawSecuredl\\Service\\SecureDownloadService', array('getHash', 'getExtensionConfiguration'), array($this->getRequestContextMock()));

		$this->fixture->expects($this->any())->method('getHash')->will($this->returnValue('abcdefgh'));
		$this->fixture->expects($this->any())->method('getExtensionConfiguration')->will($this->returnValue(array(
			'securedDirs' => 'fileadmin/secure/',
			'domain' => '/',
			'filetype' => 'jpe?g|pdf',
			'cachetimeadd' => 0,
			'linkFormat' => 'securedl/###FEUSER###/###FEGROUPS###/###TIMEOUT###/###HASH###/###FILE###',
		)));

		$actualHtml = $this->fixture->parseContent($originalHtml);

		$this->assertSame($expectedHtml, $actualHtml);
	}
}

?>