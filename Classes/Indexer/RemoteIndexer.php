<?php

namespace Xima\XmKesearchRemote\Indexer;

use TYPO3\CMS\Core\Utility\GeneralUtility;

class RemoteIndexer
{

    /**
     * Registers the indexer configuration
     *
     * @param array $params
     * @param $pObj
     */
    public function registerIndexerConfiguration(array &$params, $pObj): void
    {
        // add item to "type" field
        $newArray = [
            'Remote Site (xm_kesearch_remote)',
            'xmkesearchremote',
            GeneralUtility::getFileAbsFileName('EXT:xm_kesearch_remote/Resources/Public/Icons/Extension.svg')
        ];
        $params['items'][] = $newArray;
    }
}
