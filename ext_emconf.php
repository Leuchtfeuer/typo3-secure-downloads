<?php

$EM_CONF['secure_downloads'] = [
    'title' => 'Secure Downloads',
    'description' => '"Secure Download": Apply TYPO3 access rights to ALL file assets (PDFs, TGZs or JPGs etc. - configurable) - protect them from direct access.',
    'category' => 'fe',
    'version' => '6.0.3',
    'state' => 'stable',
    'author' => 'Dev Leuchtfeuer',
    'author_email' => 'dev@Leuchtfeuer.com',
    'author_company' => 'Leuchtfeuer Digital Marketing',
    'constraints' => [
        'depends' => [
            'php' => '8.1.0-8.3.99',
            'typo3' => '12.4.0-12.9.99',
        ],
        'conflicts' => [
            'naw_securedl' => '',
        ],
        'suggests' => [],
    ],
];
