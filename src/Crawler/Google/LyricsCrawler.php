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

use Chakavang\Crawlman\Util\TextUtil;
use Chakavang\Crawlman\Type\LyricsCrawlerInterface;
use Symfony\Component\DomCrawler\Crawler;

final class LyricsCrawler extends AbstractCrawler implements LyricsCrawlerInterface
{
    private $lyricsContent;

    private $lyricsSourceTitle;

    private $lyricsSourceUrl;

    private $lyricsSongwriters;

    private $lyricsCopyrightText;

    protected function makeUrl(string $query, ?string $langCode = null): string
    {
        $dictionary = [
            "default" => "song lyrics",
            "en" => "song lyrics",
            //"fa" => "متن ترانه"
        ];

        $query .= ' ' . ($dictionary[$langCode] ?? $dictionary["default"]);

        return parent::makeUrl($query, $langCode);
    }

    protected function crawl(Crawler $crawler, ?string $fragmentId = null): void
    {
        //
    }

    private function crawlLyricsContent(): void
    {
        // To avoid multiple calling after warnings.
        $this->lyricsContent = '';

        // Just select the first long text.
        $longTextNode = $this->getCrawler()
            ->filterXPath("//div[string-length(text()) > 350] | //span[string-length(text()) > 350]")
            ->first();

        if (0 === count($longTextNode)) {
            $this->logger->warning("[Google Music Crawler] Could not find the lyrics text.");
            return;
        }

        $this->lyricsContent = $longTextNode->text();
    }

    private function crawlLyricsSource(): void
    {
        // To avoid multiple calling after warnings.
        $this->lyricsSourceTitle = '';
        $this->lyricsSourceUrl = '';

        $sourceNode = $this->getCrawler()
            ->filterXPath("//div[contains(text(), 'Source:')]")->first();

        if (0 === count($sourceNode)) {
            $this->logger->warning("[Google Music Crawler] Could not find the source section.");
            return;
        }

        $linkNode = $sourceNode->filter("a")->first();

        if (0 === count($linkNode)) {
            $this->logger->warning("[Google Music Crawler] Could not find link of the source.");
            return;
        }

        $this->lyricsSourceTitle = $linkNode->text();
        $this->lyricsSourceUrl = $linkNode->attr("href");
    }

    private function crawlLyricsCopyright(): void
    {
        static $s = 0;
        // To avoid multiple calling after warnings.
        $this->lyricsSongwriters = [];
        $this->lyricsCopyrightText = '';

        $copyrightNode = $this->getCrawler()
            ->filterXPath("//div[contains(text(), 'Songwriters:')]")->first();

        if (0 === count($copyrightNode)) {
            $this->logger->warning("[Google Music Crawler] Could not find the copyright section.");
            return;
        }

        $text = \trim($copyrightNode->text());
        $songwritersText = \mb_substr(
            TextUtil::getFirstLine($text),
            \strlen('Songwriters:'));
        $this->lyricsSongwriters = TextUtil::trimArray(explode('/', $songwritersText));
        $secLine = TextUtil::getLine(2, $text);

        if (empty($secLine)) {
            $this->logger->warning("[Google Music Crawler] Could not find the copyright text in second line.");
            return;
        }

        // We can not receive "©" sign, thus remove it (if appeared in the future)
        // and work with "lyrics" text to remove first part of the copyright text
        // which is not necessary and Google display it for every lyrics
        // even without copyright text.
        $secLine = \str_replace('©', '', $secLine);
        $this->lyricsCopyrightText = \trim(TextUtil::extractAfter(' lyrics ', $secLine));
    }

    public function getOtherLanguage(string $langCode): ?LyricsCrawlerInterface
    {
        return null;
    }

    public function getLyricsContent(): ?string
    {
        if (null === $this->lyricsContent) {
            $this->crawlLyricsContent();
        }

        return $this->lyricsContent;
    }

    public function getLyricsSourceTitle(): ?string
    {
        if (null === $this->lyricsSourceTitle) {
            $this->crawlLyricsSource();
        }

        return $this->lyricsSourceTitle;
    }

    public function getLyricsSourceUrl(): ?string
    {
        if (null === $this->lyricsSourceUrl) {
            $this->crawlLyricsSource();
        }

        return $this->lyricsSourceUrl;
    }

    public function getLyricsSongwriters(): array
    {
        if (null === $this->lyricsSongwriters) {
            $this->crawlLyricsCopyright();
        }

        return $this->lyricsSongwriters;
    }

    public function getLyricsCopyrightText(): ?string
    {
        if (null === $this->lyricsCopyrightText) {
            $this->crawlLyricsCopyright();
        }

        return $this->lyricsCopyrightText;
    }
}
