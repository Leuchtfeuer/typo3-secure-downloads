<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "naw_securedl".
 *
 * Auto generated 05-03-2015 16:23
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array(
	'title' => 'Secure Downloads',
	'description' => '"Secure Download": Apply TYPO3 access rights to ALL file assets (PDFs, TGZs or JPGs etc. - configurable) - protect them from direct access.',
	'category' => 'fe',
	'shy' => 0,
	'version' => '1.8.2',
	'dependencies' => 'cms',
	'conflicts' => '',
	'priority' => '',
	'loadOrder' => '',
	'module' => 'modLog',
	'state' => 'stable',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearcacheonload' => 0,
	'lockType' => '',
	'author' => 'Dietrich Heise, Helmut Hummel',
	'author_email' => 'typo3-ext(at)bitmotion.de',
	'author_company' => '<a href="http://www.bitmotion.de" target="_blank">bitmotion.de</a>',
	'CGLcompliance' => '',
	'CGLcompliance_note' => '',
	'constraints' => array(
		'depends' => array(
			'cms' => '',
			'php' => '5.3.0-5.5.99',
			'typo3' => '4.5.0-6.2.99',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'suggests' => array(
	),
	'_md5_values_when_last_written' => 'a:47:{s:9:"ChangeLog";s:4:"7dc4";s:13:"composer.json";s:4:"1ac1";s:21:"ext_conf_template.txt";s:4:"fc04";s:12:"ext_icon.gif";s:4:"2dbb";s:17:"ext_localconf.php";s:4:"d809";s:14:"ext_tables.php";s:4:"9fc5";s:14:"ext_tables.sql";s:4:"ec93";s:8:"TODO.txt";s:4:"014d";s:46:"Classes/Configuration/ConfigurationManager.php";s:4:"8f49";s:28:"Classes/Core/ClassLoader.php";s:4:"22d6";s:30:"Classes/Core/ObjectManager.php";s:4:"abe6";s:29:"Classes/Parser/HtmlParser.php";s:4:"2207";s:46:"Classes/Parser/HtmlParserDelegateInterface.php";s:4:"9b32";s:34:"Classes/Request/RequestContext.php";s:4:"eae4";s:33:"Classes/Resource/FileDelivery.php";s:4:"a918";s:45:"Classes/Resource/UrlGenerationInterceptor.php";s:4:"54e9";s:64:"Classes/Resource/Publishing/AbstractResourcePublishingTarget.php";s:4:"60d9";s:80:"Classes/Resource/Publishing/Apache2DeliveryProtectedResourcePublishingTarget.php";s:4:"6549";s:76:"Classes/Resource/Publishing/PhpDeliveryProtectedResourcePublishingTarget.php";s:4:"afe1";s:49:"Classes/Resource/Publishing/ResourcePublisher.php";s:4:"8698";s:65:"Classes/Resource/Publishing/ResourcePublishingTargetInterface.php";s:4:"2628";s:79:"Classes/Security/Authorization/Resource/AccessRestrictionPublisherInterface.php";s:4:"9df2";s:70:"Classes/Security/Authorization/Resource/AccessTokenCookiePublisher.php";s:4:"af3f";s:77:"Classes/Security/Authorization/Resource/Apache2AccessRestrictionPublisher.php";s:4:"93e0";s:41:"Classes/Service/SecureDownloadService.php";s:4:"1b90";s:52:"Classes/TYPO3/CMS/Controller/ShowImageController.php";s:4:"f433";s:36:"Classes/Xclass/class.ux_fileList.inc";s:4:"bae4";s:44:"Classes/Xclass/class.ux_SC_tslib_showpic.php";s:4:"c266";s:33:"Migrations/Code/ClassAliasMap.php";s:4:"03de";s:46:"Migrations/Code/CompatibilityClassAliasMap.php";s:4:"6898";s:37:"Resources/Private/Examples/_.htaccess";s:4:"e99d";s:43:"Resources/Private/Examples/_.htaccess_allow";s:4:"5057";s:42:"Resources/Private/Examples/_.htaccess_deny";s:4:"e99d";s:43:"Resources/Private/Scripts/Compatibility.php";s:4:"3bee";s:55:"Resources/Private/Scripts/FileDeliveryEidDispatcher.php";s:4:"c5d2";s:36:"Tests/Unit/Parser/HtmlParserTest.php";s:4:"3ee4";s:41:"Tests/Unit/Request/RequestContextTest.php";s:4:"6aad";s:40:"Tests/Unit/Resource/FileDeliveryTest.php";s:4:"9f75";s:83:"Tests/Unit/Resource/Publishing/PhpDeliveryProtectedResourcePublishingTargetTest.php";s:4:"9142";s:48:"Tests/Unit/Service/SecureDownloadServiceTest.php";s:4:"5db0";s:14:"doc/manual.sxw";s:4:"7d36";s:37:"modLog/class.tx_nawsecuredl_table.php";s:4:"6044";s:15:"modLog/conf.php";s:4:"9c2f";s:16:"modLog/index.php";s:4:"3174";s:20:"modLog/locallang.xml";s:4:"71cc";s:24:"modLog/locallang_mod.xml";s:4:"84d1";s:21:"modLog/moduleicon.gif";s:4:"691d";}',
);

?>