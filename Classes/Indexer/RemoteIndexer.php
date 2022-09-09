<?php

namespace Xima\XmKesearchRemote\Indexer;

use Psr\Log\LoggerInterface;
use Tpwd\KeSearch\Indexer\IndexerRunner;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class RemoteIndexer
{
    protected ExtensionConfiguration $extensionConfiguration;

    private LoggerInterface $logger;

    public function __construct(ExtensionConfiguration $extensionConfiguration, LoggerInterface $logger)
    {
        $this->extensionConfiguration = $extensionConfiguration;
        $this->logger = $logger;
    }

    /**
     * @param array{items: array<mixed>} $params
     */
    public function registerIndexerConfiguration(array &$params): void
    {
        $params['items'][] = [
            'Remote Site (xm_kesearch_remote)',
            'xmkesearchremote',
            GeneralUtility::getFileAbsFileName('EXT:xm_kesearch_remote/Resources/Public/Icons/Extension.svg')
        ];
    }

    /**
     * sf_event_mgt indexer for ke_search
     *
     * @param array $indexerConfig Configuration from TYPO3 Backend
     * @param IndexerRunner $indexerObject Reference to indexer class.
     * @return string Output.
     */
    public function customIndexer(array &$indexerConfig, IndexerRunner &$indexerObject): string
    {
        if ($indexerConfig['type'] !== 'xmkesearchremote') {
            return '';
        }

        //$sitemaps = $this->getSitemapUrlsFromIndexerConfigurations();
        //
        //foreach ($sitemaps as $sitemap) {
        //
        //}
        //
        //$indexerObject->storeInIndex(
        //    $indexerConfig['storagepid'], // storage PID
        //    $title, // record title
        //    'xmkesearchremote', // content type
        //    $indexerConfig['targetpid'], // target PID: where is the single view?
        //    $fullContent, // indexed content, includes the title (linebreak after title)
        //    $tags, // tags for faceted search
        //    $params, // typolink params for singleview
        //    $teaser, // abstract; shown in result list if not empty
        //    $event['sys_language_uid'], // language uid
        //    $event['starttime'], // starttime
        //    $event['endtime'], // endtime
        //    $event['fe_group'], // fe_group
        //    false, // debug only?
        //    $additionalFields // additionalFields
        //);

        return '';
    }

    /**
     * @return string[]
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected function getSitemapUrlsFromIndexerConfigurations(): array
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_kesearch_indexerconfig');
        $qb->setRestrictions($qb->getRestrictions()->removeAll());
        $result = $qb->select('tx_xmkesearchremote_sitemap')
            ->from('tx_kesearch_indexerconfig')
            ->where($qb->expr()->neq('tx_xmkesearchremote_sitemap', $qb->createNamedParameter('', \PDO::PARAM_STR)))
            ->execute();

        if (is_int($result)) {
            return [];
        }

        $sitemaps = $result->fetchAllAssociative();

        return array_map(function($sitemap) {
            return $sitemap['tx_xmkesearchremote_sitemap'] ?? '';
        }, $sitemaps);
    }
}
