<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "naw_securedl".
 *
 * Auto generated 13-10-2014 08:53
 *
 * Manual updates:
 * Only the data in the array - everything else is removed by next
 * writing. "version" and "dependencies" must not be touched!
 ***************************************************************/

$EM_CONF[$_EXTKEY] = array (
	'title' => 'Secure Downloads',
	'description' => '"Secure Download": Apply TYPO3 access rights to ALL file assets (PDFs, TGZs or JPGs etc. - configurable) - protect them from direct access.',
	'category' => 'fe',
	'shy' => 0,
	'version' => '1.8.1',
	'dependencies' => '',
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
	'CGLcompliance' => NULL,
	'CGLcompliance_note' => NULL,
	'constraints' =>
		array (
			'depends' =>
				array (
					'cms' => '',
					'php' => '5.3.0-5.5.99',
					'typo3' => '4.5.0-6.2.99',
				),
			'conflicts' =>
				array (
				),
			'suggests' =>
				array (
				),
		),
);

?>