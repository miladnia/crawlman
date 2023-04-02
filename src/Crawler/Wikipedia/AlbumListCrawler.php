<?php

/*
 * This file is part of the Chakavang package.
 *
 * (c) Milad Nia <milad@miladnia.ir>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chakavang\Crawlman\Crawler\Wikipedia;

use Chakavang\Crawlman\Util\TextUtil;
use Chakavang\Crawlman\Type\AlbumListCrawlerInterface;
use Chakavang\Crawlman\Util\XPathUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\DomCrawler\Crawler;

final class AlbumListCrawler extends AbstractListCrawler implements AlbumListCrawlerInterface
{
    const TAG = "[Wikipedia Crawler] [AlbumList] ";

    /**
     * @var array
     */
    private $albumList = [];

    /**
     * @var bool
     */
    private $discographyState;

    public function __construct(string $url, ?string $langCode, bool $cacheMode, ?string $cacheDirectory, LoggerInterface $logger, bool $discography = false)
    {
        $this->discographyState = $discography;
        parent::__construct($url, $langCode, $cacheMode, $cacheDirectory, $logger);
    }

    protected function crawl(Crawler $crawler, ?string $fragmentId = null): void
    {
        if ($this->discographyState) {
            $this->crawlDiscography($crawler);
            return;
        }

        $this->crawlArtistPage($crawler);

        $discographyUrl = $this->findDiscographyLink($crawler);
        if (null !== $discographyUrl) {
            $this->addAlbum(null, "----- DISCOGRAPHY -----", 0);
            $this->merge(new self(
                $discographyUrl,
                $this->getLangCode(),
                $this->cacheMode,
                $this->cacheDirectory,
                $this->logger,
                true));
        }
    }

    private function crawlArtistPage(Crawler $crawler): void
    {
        $dictionary = [
            "en" => ["Discography"],
            "fa" => ["آلبوم‌شناسی", "ترانه‌شناسی", "آهنگ‌شناسی", "آلبوم‌ها"],
            "tr" => ["Diskografi"]
        ];

        $keywords = $dictionary[$this->getLangCode()] ?? $dictionary["en"];

        $headLineNode = $crawler->filterXPath(sprintf('//h2[%s or %s]',
            XPathUtil::makeSearchCondition($keywords, "text()"),
            XPathUtil::makeSearchCondition($keywords, "./span/text()")));

        if (0 === \count($headLineNode)) {
            $this->logger->error(self::TAG . "Could not found the discography section.", [
                't'=>sprintf('//h2[%s or %s]',
                    XPathUtil::makeSearchCondition($keywords, "text()"),
                    XPathUtil::makeSearchCondition($keywords, "./span/text()"))
            ]);
            return;
        }

        $listNode = $headLineNode->nextAll()->filter("ul, ol")->first();

        if (0 === \count($listNode)) {
            $this->logger->error(self::TAG . "Album list not found.");
            return;
        }

        $listItems = $listNode->filter("li");

        foreach ($listItems as $liNode) {
            $liText = TextUtil::convertNumbersToLatin($liNode->nodeValue);
            $matchResult = preg_match(
                "/(\(?([0-9]+)\)? *)?([^\(\)]+)( *\([^0-9]+\))? *(\(([0-9]+).*\))?$/",
                $liText, $liTextMatches);

            if (1 !== $matchResult) {
                continue;
            }

            $albumYear = (int) ($liTextMatches[6] ?? $liTextMatches[2]);
            $albumName = trim($liTextMatches[3]);
            $albumUrl = Utils::exportValidUrl(
                $liNode->getElementsByTagName("a")->item(0));

            if (null !== $albumUrl) {
                $albumUrl = Utils::queryToUrl(
                    $albumUrl, $this->getLangCode());
            }

            $this->addAlbum($albumUrl, $albumName, $albumYear);
        }
    }

    private function crawlDiscography(Crawler $crawler): void
    {
        $crawler->filter(".mw-parser-output > h3 .mw-headline")
            ->each(function (Crawler $crawler) {
                $elementId = $crawler
                    ->getNode(0)
                    ->getAttribute("id");
                $url = $this->getUrl() . "#" . $elementId;
                $nodes = $crawler->filter('a[href^="/wiki/"]');
                if (0 < count($nodes)) {
                    $title = $nodes->getNode(0)->nodeValue;
                } else {
                    $title = $crawler->getNode(0)->textContent;
                }
                $title = TextUtil::convertPersianNumbers($title);
                $year = 0;
                $matchResult = preg_match("/([^\(\[]+).*\(([0-9]+).*\)/", $title, $matches);
                if (1 === $matchResult) {
                    $title = $matches[1];
                    $year = $matches[2];
                }
                $this->addAlbum($url, $title, $year);
            });
    }

    private function findDiscographyLink(Crawler $crawler): ?string
    {
        $dictionary = [
            "en" => "Discography",
            "fa" => "ترانه‌شناسی"
        ];

        $keyword = urlencode($dictionary[$this->getLangCode()] ?? $dictionary["en"]);
        $link = $crawler->filter('a[href^="/wiki/"][href*="'. $keyword .'"]');

        if (0 === count($link)) {
            return null;
        }

        $href = $link->getNode(0)
            ->getAttribute("href");

        return Utils::queryToUrl($href, $this->getLangCode());
    }

    private function merge(self $albumListCrawler)
    {
        $this->albumList = array_merge($this->albumList, $albumListCrawler->albumList);
    }

    private function addAlbum(?string $url, ?string $name, int $year)
    {
        $this->albumList[] = [
            "url" => $url,
            "name" => $name,
            "year" => $year
        ];
    }

    public function getOtherLanguage(string $langCode): ?AlbumListCrawlerInterface
    {
        if ($this->isLang($langCode)) {
            return $this;
        }

        $url = $this->findLanguageLink($langCode);
        if (null === $url) {
            return null;
        }

        return new self(
            $url,
            $langCode,
            $this->cacheMode,
            $this->cacheDirectory,
            $this->logger);
    }

    public function getAlbumUrl(): ?string
    {
        if ($this->isItemRejected()) {
            return null;
        }

        return $this->albumList[$this->getItemPosition()]["url"] ?? null;
    }

    public function getAlbumName(): ?string
    {
        if ($this->isItemRejected()) {
            return null;
        }

        return $this->albumList[$this->getItemPosition()]["name"] ?? null;
    }

    public function getAlbumYear(): int
    {
        if ($this->isItemRejected()) {
            return 0;
        }

        return $this->albumList[$this->getItemPosition()]["year"] ?? 0;
    }

    protected function getItemCount(): int
    {
        return count($this->albumList);
    }
}
