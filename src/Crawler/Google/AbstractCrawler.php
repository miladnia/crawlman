<?php

/*
 * This file is part of the Chakavang package.
 *
 * (c) Milad Nia <milad@miladnia.ir>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chakavang\Crawlman\Crawler\Google;

abstract class AbstractCrawler extends \Chakavang\Crawlman\AbstractCrawler
{
    protected function makeUrl(string $query, ?string $langCode = null): string
    {
        return "https://www.google.com/search?q=" . Utils::encodeQueryValue($query);
    }

    protected function matchUrl(string $url): bool
    {
        return Utils::matchUrl($url);
    }

    protected function extractUrlLang(string $url): string
    {
        return "en";
    }

    protected function extractUrlQuery(string $url): ?string
    {
        preg_match("/\/search\?q=([^&]+)/", $url, $matches);
        return empty($matches) ? null : $matches[1];
    }

    protected function getCodeName(): string
    {
        return "google";
    }
}
