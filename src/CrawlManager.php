<?php

/*
 * This file is part of the Chakavang package.
 *
 * (c) Milad Nia <milad@miladnia.ir>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chakavang\Crawlman;

final class CrawlManager
{
    const LANG_ENGLISH = 'en';
    const LANG_PERSIAN = 'fa';

    /**
     * @var FactoryInterface[]
     */
    private $factories = [];

    public function registerCrawler(FactoryInterface $crawlerFactory, string $type)
    {
        $this->factories[$type][] = $crawlerFactory;
        return $this;
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function getCrawler(string $url, string $type, ?string $langCode = null): CrawlerInterface
    {
        if (empty($this->factories[$type])) {
            throw new \InvalidArgumentException("\"{$type}\" type is not defined.");
        }

        foreach ($this->factories[$type] as $f) {
            if ($f->matchUrl($url)) {
                return $f->createCrawler($url, $langCode);
            }
        }

        throw new \InvalidArgumentException("there is not any crawler for url \"{$url}\"");
    }
}
