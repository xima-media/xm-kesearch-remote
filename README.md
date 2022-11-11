# TYPO3 extension `xm_kesearch_remote`

This extension adds a new indexer for [ke_search](https://extensions.typo3.org/extension/ke_search) that fetches and indexes remote websites. The remote website needs to offer a `sitemap.xml`.

## Installation

```
composer require xima/xm-kesearch-remote
```

## Configuration

Create a new indexer and select `xm_kesearch_remote`. Add the URL to the remote sitemap which contains the links to index.

![Remote Indexer Backend](Documentation/Images/remote-indexer.jpg)

The indexer will crawl all links in the sitemap and cache the fetched content as json files. In the extension configuration you can change the cache directory.

To reduce the amount of downloaded data, you can filter the DOM with a css-like filter in the `Filter` field.

You can select a specific language the indexed data will be assigned to.


