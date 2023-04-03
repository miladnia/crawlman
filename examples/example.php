<?php

require_once dirname(__DIR__) . '/vendor/autoload.php';

use Chakavang\Crawlman\CrawlManager;

($cm = new CrawlManager)
    ->registerCrawler(new Chakavang\Crawlman\Crawler\Wikipedia\ArtistCrawlerFactory(false, dirname(__DIR__).'/cache'), 'ART')
    ->registerCrawler(new Chakavang\Crawlman\Crawler\Wikipedia\AlbumCrawlerFactory, 'ALB')
    ->registerCrawler(new Chakavang\Crawlman\Crawler\Wikipedia\AlbumListCrawlerFactory, 'ALBLS')
    ->registerCrawler(new Chakavang\Crawlman\Crawler\Google\AlbumCrawlerFactory, 'ALB')
    ->registerCrawler(new Chakavang\Crawlman\Crawler\Google\LyricsCrawlerFactory, 'LYR');

/**
 * Scraping the Wikipedia page of "Michael Jackson".
 * According to the "url" and "type", the relevant crawler will be returned.
 * 
 * @var Chakavang\Crawlman\Type\ArtistCrawlerInterface $crawler
 */
$crawler = $cm->getCrawler(
    'https://en.wikipedia.org/wiki/Michael_Jackson', 'ART', CrawlManager::LANG_ENGLISH);

echo $crawler->getArtistName() . " ("
    . $crawler->getArtistRealName() . ") "
    . $crawler->getArtistBirthday()->format('Y') . "-"
    . $crawler->getArtistDeathDate()->format('Y')

    . "\n- Born in \""
    . $crawler->getArtistBirthPlace(). "\""

    . "\n- Years active ["
    . $crawler->getArtistActiveYearsFrom() . "-"
    . $crawler->getArtistActiveYearsTo() . "]"

    . "\n- Genres: "
    . implode(', ', $crawler->getArtistGenres())

    . "\n- Occupations: "
    . implode(', ', $crawler->getArtistOccupations())

    . "\n- Instruments: "
    . implode(', ', $crawler->getArtistInstruments())

    . "\n- Link: "
    . $crawler->getArtistWebsiteAddress()

    . "\n";
