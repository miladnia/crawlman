<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Chakavang\Crawlman\CrawlManager;

($cm = new CrawlManager)
    ->registerCrawler(new Chakavang\Crawlman\Crawler\Wikipedia\ArtistCrawlerFactory(false, dirname(__DIR__).'/cache'), 'ART')
    ->registerCrawler(new Chakavang\Crawlman\Crawler\Wikipedia\AlbumCrawlerFactory, 'ALB')
    ->registerCrawler(new Chakavang\Crawlman\Crawler\Wikipedia\AlbumListCrawlerFactory, 'ALBLS')
    ->registerCrawler(new Chakavang\Crawlman\Crawler\Google\AlbumCrawlerFactory, 'ALB')
    ->registerCrawler(new Chakavang\Crawlman\Crawler\Google\LyricsCrawlerFactory, 'LYR');

/** @var Chakavang\Crawlman\Type\ArtistCrawlerInterface */
$crawler = $cm->getCrawler(
    'https://en.wikipedia.org/wiki/Michael_Jackson', 'ART', CrawlManager::LANG_ENGLISH);

echo $crawler->getArtistName() . "\n- Born in ("
    . $crawler->getArtistBirthPlace() . ")\n- Years active ["
    . $crawler->getArtistActiveYearsFrom() . "-"
    . $crawler->getArtistActiveYearsTo() . "]\n- Genres: "
    . implode(', ', $crawler->getArtistGenres()) . "\n";
