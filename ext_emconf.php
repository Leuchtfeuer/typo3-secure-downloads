<?php

$EM_CONF[$_EXTKEY] = array(
    'title' => 'Secure Downloads',
    'description' => '"Secure Download": Apply TYPO3 access rights to ALL file assets (PDFs, TGZs or JPGs etc. - configurable) - protect them from direct access.',
    'category' => 'fe',
    'version' => '2.0.4-dev',
    'state' => 'beta',
    'uploadfolder' => false,
    'createDirs' => '',
    'clearCacheOnLoad' => true,
    'author' => 'Florian Wessels, Helmut Hummel',
    'author_email' => 'typo3-ext@bitmotion.de',
    'author_company' => 'Bitmotion GmbH',
    'constraints' => array(
        'depends' => array(
            'php' => '5.3.0-7.0.99',
            'typo3' => '6.2.0-8.5.99',
        ),
        'conflicts' => array(
            'naw_securedl' => '',
        ),
        'suggests' => array(
        ),
    ),
);
