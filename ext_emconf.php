<?php

$EM_CONF[$_EXTKEY] = [
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
    'constraints' => [
        'depends' => [
            'php' => '5.5.0-7.1.99',
            'typo3' => '7.6.0-8.7.99',
        ],
        'conflicts' => [
            'naw_securedl' => '',
        ],
        'suggests' => [
        ],
    ],
];
