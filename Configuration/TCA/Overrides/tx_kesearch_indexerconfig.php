<?php

$fields = [
    'tx_xmkesearchremote_sitemap' => [
        'exclude' => 0,
        'label' => 'Sitemap URL',
        'displayCond' => 'FIELD:type:=:xmkesearchremote',
        'config' => [
            'type' => 'input',
            'eval' => 'trim,required',
        ],
    ],
    'tx_xmkesearchremote_filter' => [
        'exclude' => 0,
        'label' => 'Filter',
        'displayCond' => 'FIELD:type:=:xmkesearchremote',
        'config' => [
            'type' => 'input',
            'eval' => 'trim',
        ],
    ],
    'tx_xmkesearchremote_language' => [
        'exclude' => 0,
        'label' => 'Langauge UID',
        'displayCond' => 'FIELD:type:=:xmkesearchremote',
        'config' => [
            'type' => 'input',
            'eval' => 'int,trim',
            'default' => 0,
        ],
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tx_kesearch_indexerconfig', $fields);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tx_kesearch_indexerconfig',
    'tx_xmkesearchremote_sitemap,tx_xmkesearchremote_filter,tx_xmkesearchremote_language',
    '',
    'after:storagepid'
);

$GLOBALS['TCA']['tx_kesearch_indexerconfig']['columns']['targetpid']['displayCond'] .= ',xmkesearchremote';
