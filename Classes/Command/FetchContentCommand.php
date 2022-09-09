<?php

namespace Xima\XmKesearchRemote\Command;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DomCrawler\Crawler;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Xima\XmKesearchRemote\Domain\Model\Dto\SitemapLink;

class FetchContentCommand extends Command
{
    protected string $cacheDir = '';

    /**
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException
     * @throws \TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException
     * @throws \Exception
     */
    public function __construct(
        ExtensionConfiguration $extensionConfiguration,
        string $name = null
    ) {
        parent::__construct($name);

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

    protected function configure(): void
    {
        $this->setDescription('Fetch and cache remote content for indexing');
        $this->setHelp('');
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\DBALException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $sitemapConfigs = $this->getSitemapConfigurationFromIndexerConfigurations();

        foreach ($sitemapConfigs as $config) {
            $xml = $this->fetchRemoteSitemap($config['tx_xmkesearchremote_sitemap']);
            $links = $this->convertXmlToLinks($xml, $config['tx_xmkesearchremote_sitemap']);
            $links = $this->filterLinksByFileTypes($links);
            $links = $this->filterLinksByCache($links);
            $this->fetchAndPersistLinks($links, $config['tx_xmkesearchremote_filter']);
        }

        return Command::SUCCESS;
    }

    /**
     * @param SitemapLink[] $links
     */
    protected function fetchAndPersistLinks(array $links, string $filter = 'body'): void
    {
        $client = new Client(['verify' => false]);
        foreach ($links as $link) {
            try {
                $response = $client->request('GET', $link->loc);
                $dom = $response->getBody()->getContents();
                $crawler = new Crawler($dom);
                $crawler = $crawler->filter('head title');
                $link->title = $crawler->html('');
                $crawler = new Crawler($dom);
                $crawler = $crawler->filter($filter);
                $link->content = preg_replace('/\s*\R\s*/', ' ', (trim(strip_tags($crawler->html(''))))) ?? '';
                $this->persistLink($link);
            } catch (GuzzleException $e) {
            }
        }
    }

    protected function persistLink(SitemapLink $link): void
    {
        $filename = $this->cacheDir . '/' . $link->getCacheIdentifier();
        $fileContent = $link->getFileContent();
        file_put_contents($filename, $fileContent);
    }

    /**
     * @param SitemapLink[] $links
     * @return SitemapLink[]
     */
    protected function filterLinksByCache(array $links): array
    {
        $nowTimestamp = (new \DateTime())->getTimestamp();

        foreach ($links as $key => $link) {
            $filename = realpath($this->cacheDir . '/' . $link->getCacheIdentifier()) ?: '';

            if (!file_exists($filename)) {
                continue;
            }

            $fileContent = file_get_contents($filename) ?: '';
            $cachedLink = unserialize($fileContent);

            if ($cachedLink instanceof SitemapLink && $link->lastmod < $nowTimestamp) {
                unset($links[$key]);
            }
        }

        return $links;
    }

    /**
     * @param SitemapLink[] $links
     * @return SitemapLink[]
     */
    protected function filterLinksByFileTypes(array $links): array
    {
        return array_filter($links, function ($link) {
            $linkParts = explode('.', $link->loc);
            return count($linkParts) === 1 || in_array(end($linkParts), ['html', 'php']);
        });
    }

    /**
     * @param string $xml
     * @return SitemapLink[]
     */
    protected function convertXmlToLinks(string $xml, string $sitemapUrl): array
    {
        if (!$xml) {
            return [];
        }

        $crawler = new Crawler($xml);

        return $crawler->filter('url')->each(function (Crawler $parentCrawler) use ($sitemapUrl) {
            $link = new SitemapLink($sitemapUrl);
            $link->loc = (string)$parentCrawler->children('loc')->getNode(0)?->nodeValue ?: '';
            $link->lastmod = (int)($parentCrawler->children('lastmod')->getNode(0)?->nodeValue ?: 0);
            return $link;
        });
    }

    protected function fetchRemoteSitemap(string $sitemapUrl): string
    {
        $client = new Client(['verify' => false]);

        try {
            $url = str_starts_with($sitemapUrl, '/') ? 'https://' . $_SERVER['SERVER_NAME'] . $sitemapUrl : $sitemapUrl;
            $response = $client->request('GET', $url);
            $xml = $response->getBody()->getContents();
        } catch (GuzzleException $e) {
        }

        return $xml ?? '';
    }

    /**
     * @return array<int, array{tx_xmkesearchremote_sitemap: string, tx_xmkesearchremote_filter: string}>
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    protected function getSitemapConfigurationFromIndexerConfigurations(): array
    {
        $qb = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_kesearch_indexerconfig');
        $qb->setRestrictions($qb->getRestrictions()->removeAll());
        $result = $qb->select('tx_xmkesearchremote_sitemap', 'tx_xmkesearchremote_filter')
            ->from('tx_kesearch_indexerconfig')
            ->where($qb->expr()->neq('tx_xmkesearchremote_sitemap', $qb->createNamedParameter('', \PDO::PARAM_STR)))
            ->execute();

        if (is_int($result)) {
            return [];
        }

        return $result->fetchAllAssociative();
    }
}
