<?php

$EM_CONF['secure_downloads'] = [
    'title' => 'Secure Downloads',
    'description' => '"Secure Download": Apply TYPO3 access rights to ALL file assets (PDFs, TGZs or JPGs etc. - configurable) - protect them from direct access.',
    'category' => 'fe',
    'version' => '5.0.6',
    'state' => 'stable',
    'clearCacheOnLoad' => true,
    'author' => 'Dev Leuchtfeuer',
    'author_email' => 'dev@Leuchtfeuer.com',
    'author_company' => 'Leuchtfeuer Digital Marketing',
    'constraints' => [
        'depends' => [
            'php' => '7.2.0-8.1.99',
            'typo3' => '10.4.0-11.9.99',
        ],
        'conflicts' => [
            'naw_securedl' => '',
        ],
        'suggests' => [],
    ],
];
