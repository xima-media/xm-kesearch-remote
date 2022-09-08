<?php
defined('TYPO3_MODE') or die();

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['registerIndexerConfiguration'][] = \Xima\XmKesearchRemote\Indexer\RemoteIndexer::class;
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['ke_search']['customIndexer'][] = \Xima\XmKesearchRemote\Indexer\RemoteIndexer::class;

// Register cache
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['xmkesearchremote_cache'] ??= [];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['xmkesearchremote_cache']['backend'] ??= \TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend::class;
