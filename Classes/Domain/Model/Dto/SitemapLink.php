<?php

namespace Xima\XmKesearchRemote\Domain\Model\Dto;

class SitemapLink
{
    public string $loc = '';

    public int $lastmod = 0;

    public string $content = '';

    public string $title = '';

    public int $fetchDate = 0;

    public string $sitemapUrl = '';

    public function __construct(string $sitemapUrl)
    {
        $this->fetchDate = (new \DateTime())->getTimestamp();
        $this->sitemapUrl = $sitemapUrl;
    }

    public function getCacheIdentifier(): string
    {
        return md5($this->sitemapUrl) . '-' . md5($this->loc);
    }

    public function getFileContent(): string
    {
        return serialize($this) ?: '';
    }

    public function getDisplayTitle(): string
    {
        return $this->title ?: $this->loc;
    }
}
