<?php

namespace Xima\XmKesearchRemote\Domain\Model\Dto;

class SitemapLink
{
    public string $loc = '';

    public int $lastmod = 0;

    public string $content = '';

    public string $title = '';

    public int $fetchDate = 0;

    public function __construct()
    {
        $this->fetchDate = (new \DateTime())->getTimestamp();
    }

    public function getCacheIdentifier(): string
    {
        return md5($this->loc);
    }
}
