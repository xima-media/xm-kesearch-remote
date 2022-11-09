<?php

namespace Xima\XmKesearchRemote\Indexer;

use Psr\Log\LoggerInterface;
use Tpwd\KeSearch\Indexer\IndexerRunner;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XmKesearchRemote\Domain\Model\Dto\SitemapLink;

class RemoteIndexer
{
    protected string $cacheDir = '';

    private LoggerInterface $logger;

    public function __construct()
    {
        $extensionConfiguration = GeneralUtility::makeInstance(ExtensionConfiguration::class);
        $cacheDirSetting = $extensionConfiguration->get('xm_kesearch_remote', 'cache_dir');
        $cacheDirPath = realpath(Environment::getPublicPath() . '/' . $cacheDirSetting);
        if (!is_string($cacheDirPath)) {
            throw new \Exception('Not a valid cache dir "' . $cacheDirPath . '"', 1662710676);
        }
        if ($cacheDirPath && !is_dir($cacheDirPath)) {
            mkdir($cacheDirPath);
        }
        if (!is_writable($cacheDirPath)) {
            throw new \Exception('Cache dir "' . $cacheDirPath . '" is not writable', 1662710675);
        }
        $this->cacheDir = $cacheDirPath;
    }

    /**
     * @param array{items: array<mixed>} $params
     */
    public function registerIndexerConfiguration(array &$params): void
    {
        $params['items'][] = [
            'Remote Site (xm_kesearch_remote)',
            'xmkesearchremote',
            GeneralUtility::getFileAbsFileName('EXT:xm_kesearch_remote/Resources/Public/Icons/Extension.svg'),
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

        $links = $this->getCachedLinksBySitemapUrl($indexerConfig['tx_xmkesearchremote_sitemap']);
        $languageUid = $indexerConfig['tx_xmkesearchremote_language'] ?? 0;

        foreach ($links as $link) {
            if (!trim($link->content)) {
                continue;
            }

            $indexerObject->storeInIndex(
                $indexerConfig['storagepid'], // storage PID
                $link->getDisplayTitle(),
                'xmkesearchremote', // content type
                $link->loc, // target PID: where is the single view?
                $link->content, // indexed content, includes the title (linebreak after title)
                '#remote#', // tags for faceted search
                '', // typolink params for singleview
                '', // abstract; shown in result list if not empty
                $languageUid, // language uid
                0, // starttime
                0, // endtime
                '', // fe_group
                false, // debug only?
                [
                    'orig_uid' => md5($link->loc),
                ] // additionalFields
            );
        }

        return 'Remote Indexer (' . $indexerConfig['title'] . '):' . count($links) . ' Elements have been indexed.</p>';
    }

    /**
     * @param string $sitemapUrl
     * @return \Xima\XmKesearchRemote\Domain\Model\Dto\SitemapLink[]
     */
    protected function getCachedLinksBySitemapUrl(string $sitemapUrl): array
    {
        $allFiles = scandir($this->cacheDir) ?: [];
        $md5 = md5($sitemapUrl);
        $filesOfSitemap = array_filter($allFiles, function ($filename) use ($md5) {
            return str_starts_with($filename, $md5);
        });
        $sitemapLinks = [];
        foreach ($filesOfSitemap as $filename) {
            $fileContent = file_get_contents($this->cacheDir . '/' . $filename) ?: '';
            $cachedLink = unserialize($fileContent);

            if ($cachedLink instanceof SitemapLink) {
                $sitemapLinks[] = $cachedLink;
            }
        }
        return $sitemapLinks;
    }
}
