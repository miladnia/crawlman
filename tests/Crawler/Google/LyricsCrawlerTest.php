<?php

namespace Chakavang\Crawlman\Tests\Crawler\Google;

use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Chakavang\Crawlman\Crawler\Google\LyricsCrawler;

class LyricsCrawlerTest extends TestCase
{
    private $crawler;

    public function __construct()
    {
        parent::__construct();
        // [gbv=1 (JavaScript disabled)]
        $url = 'https://www.google.com/search?q=ava+max+so+do+i+lyrics&gbv=1';
        $this->crawler = new LyricsCrawler($url, 'en', false, dirname(dirname(dirname(__DIR__))).'/cache', new NullLogger);
    }

    public function testGetLyricsContent()
    {
        $this->assertContains('Do you ever feel like a misfit?', $this->crawler->getLyricsContent());
    }

    public function testGetLyricsSourceTitle()
    {
        $this->assertEquals('LyricFind', $this->crawler->getLyricsSourceTitle());
    }

    public function testGetLyricsSourceUrl()
    {
        $this->assertEquals('https://www.lyricfind.com/', $this->crawler->getLyricsSourceUrl());
    }

    public function testGetLyricsSongwriters()
    {
        $this->assertEquals('Amanda Ava Koci / Charlie Puth / Gigi Grombacher / Henry Russell Walter / Maria Jane Smith / Roland Spreckley / Victor Thell',
            implode(' / ', $this->crawler->getLyricsSongwriters()));
    }

    public function testGetLyricsCopyrightText()
    {
        $this->assertEquals('Sony/ATV Music Publishing LLC, Warner Chappell Music, Inc, Kobalt Music Publishing Ltd.',
            $this->crawler->getLyricsCopyrightText());
    }
}
