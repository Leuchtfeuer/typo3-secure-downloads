<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Secure Downloads',
    'description' => '"Secure Download": Apply TYPO3 access rights to ALL file assets (PDFs, TGZs or JPGs etc. - configurable) - protect them from direct access.',
    'category' => 'fe',
    'version' => '2.0.1',
    'state' => 'beta',
    'uploadfolder' => false,
    'createDirs' => '',
    'internal' => '',
    'clearcacheonload' => true,
    'author' => 'Florian Wessels, Helmut Hummel',
    'author_email' => 'typo3-ext(at)bitmotion.de',
    'constraints' => [
        'depends' => [
            'php' => '5.5.0',
            'typo3' => '6.2.0',
        ],
        'conflicts' => [
            'naw_securedl' => '',
        ],
        'suggests' => [
        ],
    ],
    'suggests' => [
    ],
];
