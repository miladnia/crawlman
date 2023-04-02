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

use Chakavang\Crawlman\Type\AlbumCrawlerInterface;
use Symfony\Component\DomCrawler\Crawler;

final class AlbumCrawler extends AbstractListCrawler implements AlbumCrawlerInterface
{
    /**
     * @var string
     */
    private $albumName;

    /**
     * @var int
     */
    private $albumYear = 0;

    /**
     * @var array
     */
    private $trackList = [];

    protected function makeUrl(string $query, ?string $langCode = null): string
    {
        $dictionary = [
            "en" => "album tracks",
//            "fa" => "ترک های آلبوم",
        ];

        $query .= ' ' . ($dictionary[$langCode] ?? $dictionary["en"]);

        return parent::makeUrl($query, $langCode);
    }

    protected function crawl(Crawler $crawler, ?string $fragmentId = null): void
    {
        $albumBy = $crawler->filterXPath("//div[starts-with(text(),'Album by ')]")->first();

        if (0 !== \count($albumBy)) {
            $this->albumName = $albumBy->parents()->parents()->children()->first()->siblings()->text();
        } else {
            $this->logger->error(self::TAG . "Album name not found.");
        }

        $this->crawlTrackList();
    }

    private function crawlTrackList(): void
    {
        $songs = $this->getCrawler()->filterXPath("//div[text()='Songs']")->first();

        if (0 === \count($songs)) {
            $this->logger->error(self::TAG . "Songs title not found.");
            return;
        }

        $songs = $songs->parents()->parents()->parents();
        $trackLinks = $songs->filter('a[href^="/search?"]');

        if (0 === \count($trackLinks)) {
            $this->logger->error(self::TAG . "Songs list not found.");
        }

        $trackCounter = 0;
        $trackLinks->each(function (Crawler $crawler) use ($trackCounter) {
            $this->addTrack(++$trackCounter, $crawler->text());
        });
    }

    private function addTrack(int $trackNumber, string $trackTitle): void
    {
        $this->trackList[] = [
            "number" => $trackNumber,
            "title" => $trackTitle
        ];
    }

    public function getOtherLanguage(string $langCode): ?AlbumCrawlerInterface
    {
        return null;
    }

    protected function getItemCount(): int
    {
        return count($this->trackList);
    }

    public function getAlbumTitle(): ?string
    {
        return $this->albumName;
    }

    public function getAlbumYear(): int
    {
        return $this->albumYear;
    }

    public function getAlbumCoverAddress(): ?string
    {
        return null;
    }

    public function getTrackNumber(): int
    {
        if ($this->isItemRejected()) {
            return 0;
        }

        return $this->trackList[$this->getItemPosition()]["number"] ?? 0;
    }

    public function getTrackTitle(): ?string
    {
        if ($this->isItemRejected()) {
            return null;
        }

        return $this->trackList[$this->getItemPosition()]["title"] ?? null;
    }

    public function getTrackWriters(): array
    {
        return [];
    }

    public function getTrackProducers(): array
    {
        return [];
    }

    public function getTrackComposers(): array
    {
        return [];
    }

    public function getTrackArrangers(): array
    {
        return [];
    }

    public function getTrackFeaturedArtists(): array
    {
        return [];
    }

    public function getTrackLabels(): array
    {
        return [];
    }

    public function getTrackBonusStatus(): bool
    {
        return false;
    }

    public function getTrackLength(): int
    {
        return 0;
    }

    public function getAlbumReleaseDate(): ?\DateTimeInterface
    {
        return null;
    }
}
