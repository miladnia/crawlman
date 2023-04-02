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

use Chakavang\Crawlman\AbstractCrawlerFactory;
use Chakavang\Crawlman\FactoryInterface;
use Chakavang\Crawlman\Type\LyricsCrawlerInterface;

class LyricsCrawlerFactory extends AbstractCrawlerFactory implements FactoryInterface
{
    public function createCrawler(string $url, ?string $langCode = null): LyricsCrawlerInterface
    {
        return new LyricsCrawler(
            $url,
            $langCode,
            $this->cacheMode,
            $this->cacheDirectory,
            $this->logger);
    }

    public function matchUrl(string $url): bool
    {
        return Utils::matchUrl($url);
    }
}
