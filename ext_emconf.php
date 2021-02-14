<?php

$EM_CONF['extender'] = [
    'title' => 'Extbase Domain Model Extender',
    'description' => 'A services that enables adding properties and functions
    to classes by implementing the proxy pattern',
    'category' => 'misc',
    'author' => 'Sebastian Fischer',
    'author_email' => 'typo3@evoweb.de',
    'author_company' => 'evoWeb',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'version' => '7.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '10.0.0-11.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
