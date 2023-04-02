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

use Chakavang\Crawlman\Type\AlbumCrawlerInterface;
use Chakavang\Crawlman\TableCrawler;
use Chakavang\Crawlman\TableRowCrawler;
use Chakavang\Crawlman\Util\DateTimeUtil;
use Chakavang\Crawlman\Util\TextUtil;
use Symfony\Component\DomCrawler\Crawler;

final class AlbumCrawler extends AbstractListCrawler implements AlbumCrawlerInterface
{
    const TAG = "[Wikipedia Crawler: Album] ";

    const LABEL_RELEASE_DATE = "release-date";

    const TRACK_NUMBER = "number";
    const TRACK_TITLE = "title";
    const TRACK_WRITER = "writer";
    const TRACK_PRODUCER = "producer";
    const TRACK_COMPOSER = "composer";
    const TRACK_ARRANGER = "arranger";
    const TRACK_LENGTH = "length";
    const TRACK_FEATURED_ARTISTS = "featuring";
    const TRACK_BONUS_STATUS = "bonus";
    const TRACK_LABELS = "label";

    private $discography = false;

    /**
     * @var string
     */
    private $albumTitle;

    /**
     * @var int
     */
    private $albumYear = 0;

    /**
     * @var \DateTimeInterface
     */
    private $albumReleaseDate;

    /**
     * @var string
     */
    private $albumCoverAddress;

    /**
     * @var array
     */
    private $trackList = [];

    protected function crawl(Crawler $crawler, ?string $fragmentId = null): void
    {
        if (null !== $fragmentId) {
            $this->discography = true;
            $this->crawlDiscography($crawler, $fragmentId);
            return;
        }

        $this->crawlAlbumPage($crawler);
    }

    private function crawlAlbumPage(Crawler $crawler): void
    {
        $dictionary = $this->getDictionary($this->getLangCode());
        $this->albumTitle = $this->readInfoBoxTitle() ?? $this->readArticleTitle();
        $this->albumCoverAddress = $this->findMainImageAddress();
        $this->albumReleaseDate = $this->readInfoBoxDate($dictionary[self::LABEL_RELEASE_DATE]);
        $this->crawlTrackList($crawler);
    }

    private function crawlDiscography(Crawler $crawler, string $fragmentId): void
    {
        // TODO check album page link, if album has single page or not.
        $headline = $crawler
            ->filter('.mw-headline[id="' . $fragmentId . '"]');
        // If headline not found.
        if (0 === count($headline)) {
            return;
        }

        $this->albumTitle = TextUtil::removeBrackets($headline->text());
        $this->albumTitle = TextUtil::removeNumbersInsideParentheses($this->albumTitle, function ($number) {
            if (1000 < $number) {
                $this->albumYear = $number;
            }
        });
        $this->crawlTrackList(
            $headline->parents()->nextAll());
    }

    private function crawlTrackList(Crawler $crawler): void
    {
        $table = $crawler->filter("table.tracklist")->first();

        if (0 === \count($table)) {
            $this->logger->warning(self::TAG . "Track list table not found.");
            $table = $crawler->filter("table.wikitable")->first();

            if (0 === \count($table)) {
                $this->logger->error(self::TAG . "Could not found a table for tracks.");
                return;
            }
        }

        $dictionary = $this->getTrackListDictionary($this->getLangCode());
        $tableCrawler = new TableCrawler($table, $dictionary);
        $tableCrawler->eachRow(function (TableRowCrawler $row) use (&$dictionary) {
            $length = $row->readCellText(self::TRACK_LENGTH);

            $track = [
                self::TRACK_NUMBER => (int) (TextUtil::convertNumbersToLatin($row->readCellText(self::TRACK_NUMBER)) ?? 1 + \count($this->trackList)),
                self::TRACK_TITLE => $row->readCellText(self::TRACK_TITLE),
                self::TRACK_WRITER => [],
                self::TRACK_PRODUCER => [],
                self::TRACK_COMPOSER => [],
                self::TRACK_ARRANGER => [],
                self::TRACK_FEATURED_ARTISTS => [],
                self::TRACK_LABELS => [],
                self::TRACK_BONUS_STATUS => false,
                self::TRACK_LENGTH => (null === $length) ? 0 :DateTimeUtil::timeToSeconds(TextUtil::convertNumbersToLatin($length)),
            ];

            if (null !== $track[self::TRACK_TITLE]) {
                $track[self::TRACK_TITLE] = \str_replace('"', '', $track[self::TRACK_TITLE]);
                $track[self::TRACK_TITLE] = TextUtil::removeParentheses($track[self::TRACK_TITLE], function (string $text) use (&$track, &$dictionary) {
                    foreach ($dictionary[self::TRACK_BONUS_STATUS] as $dicVal) {
                        if (TextUtil::contains($dicVal . ' ', $text)) {
                            $track[self::TRACK_BONUS_STATUS] = true;
                            return;
                        }
                    }

                    foreach ($dictionary[self::TRACK_FEATURED_ARTISTS] as $dicVal) {
                        $featuring = \trim(TextUtil::extractAfter($dicVal . ' ', $text, ''));
                        if ('' !== $featuring) {
                            $track[self::TRACK_FEATURED_ARTISTS] = TextUtil::explode($featuring);
                            return;
                        }
                    }

                    $track[self::TRACK_LABELS][] = $text;
                });
            }

            foreach ($this->getMultiItemTrackListColumns() as $columnKey) {
                $row->readMultiItemCell($columnKey, function (string $text, ?string $url) use (&$track, $columnKey) {
                    $text = TextUtil::removeParentheses(TextUtil::removeBrackets($text));

                    if ((null === $url) || !Utils::isWikiQuery($url)) {
                        $track[$columnKey][] = $text;
                        return;
                    }

                    $url = Utils::queryToUrl($url, $this->getLangCode());

                    if (isset($track[$columnKey][$url])) {
                        $url .= '#' . $text;
                    }

                    $track[$columnKey][$url] = $text;
                });
            }

            $this->trackList[] = $track;
        });
    }

    public function getDictionary(string $langCode): array
    {
        $dictionary = [
            "en" => [
                self::LABEL_RELEASE_DATE => ["release date"],
            ],
            "fa" => [
                self::LABEL_RELEASE_DATE => ["انتشار", "تاریخ انتشار"],
            ],
            "tr" => [
                self::LABEL_RELEASE_DATE => ["Yayımlanma"],
            ],
        ];

        return $dictionary[$langCode] ?? $dictionary["en"];
    }

    private function getTrackListDictionary(string $langCode): array
    {
        $dictionary = [
            "en" => [
                self::TRACK_NUMBER => ["#", "no.", "track"],
                self::TRACK_TITLE => ["title"],
                self::TRACK_WRITER => ["writer", "writer(s)", "songwriter", "songwriter(s)"],
                self::TRACK_PRODUCER => ["producer", "producer(s)", "music"],
                self::TRACK_COMPOSER => ["composer"],
                self::TRACK_ARRANGER => [],
                self::TRACK_LENGTH => ["length"],
                self::TRACK_FEATURED_ARTISTS => ["featuring", "feat.", "with"],
                self::TRACK_BONUS_STATUS => ["bonus"],
            ],
            "fa" => [
                self::TRACK_NUMBER => ["#", "شماره", "ردیف"],
                self::TRACK_TITLE => ["نام", "نام ترانه", "عنوان", "آهنگ"],
                self::TRACK_WRITER => ["ترانه‌سرا", "ترانه سرا", "سراینده", "نویسنده", "نویسنده(ها)"],
                self::TRACK_PRODUCER => [],
                self::TRACK_COMPOSER => ["آهنگساز", "آهنگ‌ساز", "آهنگ‌ساز(ها)"],
                self::TRACK_ARRANGER => ["تنظیم‌کننده", "تنظیم کننده", "تنظیم"],
                self::TRACK_LENGTH => ["مدت", "زمان"],
                self::TRACK_FEATURED_ARTISTS => [],
                self::TRACK_BONUS_STATUS => [],
            ],
            "tr" => [
                self::TRACK_NUMBER => ["#", "no."],
                self::TRACK_TITLE => ["şarkı", "başlık"],
                self::TRACK_WRITER => ["söz", "söz yazar(ları)ı", "şarkı yazar(lar)ı"],
                self::TRACK_PRODUCER => [],
                self::TRACK_COMPOSER => ["müzik", "besteci(ler)"],
                self::TRACK_ARRANGER => [],
                self::TRACK_LENGTH => ["süre"],
                self::TRACK_FEATURED_ARTISTS => [],
                self::TRACK_BONUS_STATUS => [],
            ],
        ];

        return $dictionary[$langCode] ?? $dictionary["en"];
    }

    private function getMultiItemTrackListColumns(): array
    {
        return [
            self::TRACK_WRITER,
            self::TRACK_PRODUCER,
            self::TRACK_COMPOSER,
            self::TRACK_ARRANGER,
        ];
    }

    public function getOtherLanguage(string $langCode): ?AlbumCrawlerInterface
    {
        // Discography not supported yet. The fragment of
        // the discography must resolve for translated pages.
        if ($this->discography) {
            return null;
        }

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

    protected function getItemCount(): int
    {
        return count($this->trackList);
    }

    public function getAlbumTitle(): ?string
    {
        return $this->albumTitle;
    }

    public function getAlbumYear(): int
    {
        return $this->albumYear;
    }

    public function getAlbumReleaseDate(): ?\DateTimeInterface
    {
        return $this->albumReleaseDate;
    }

    public function getAlbumCoverAddress(): ?string
    {
        return $this->albumCoverAddress;
    }

    public function getTrackNumber(): int
    {
        if ($this->isItemRejected()) {
            return 0;
        }

        return $this->trackList[$this->getItemPosition()][self::TRACK_NUMBER];
    }

    public function getTrackTitle(): ?string
    {
        if ($this->isItemRejected()) {
            return null;
        }

        return $this->trackList[$this->getItemPosition()][self::TRACK_TITLE];
    }

    public function getTrackWriters(): array
    {
        if ($this->isItemRejected()) {
            return [];
        }

        return $this->trackList[$this->getItemPosition()][self::TRACK_WRITER];
    }

    public function getTrackProducers(): array
    {
        if ($this->isItemRejected()) {
            return [];
        }

        return $this->trackList[$this->getItemPosition()][self::TRACK_PRODUCER];
    }

    public function getTrackComposers(): array
    {
        if ($this->isItemRejected()) {
            return [];
        }

        return $this->trackList[$this->getItemPosition()][self::TRACK_COMPOSER];
    }

    public function getTrackArrangers(): array
    {
        if ($this->isItemRejected()) {
            return [];
        }

        return $this->trackList[$this->getItemPosition()][self::TRACK_ARRANGER];
    }

    public function getTrackFeaturedArtists(): array
    {
        if ($this->isItemRejected()) {
            return [];
        }

        return $this->trackList[$this->getItemPosition()][self::TRACK_FEATURED_ARTISTS];
    }

    public function getTrackLabels(): array
    {
        if ($this->isItemRejected()) {
            return [];
        }

        return $this->trackList[$this->getItemPosition()][self::TRACK_LABELS];
    }

    public function getTrackBonusStatus(): bool
    {
        if ($this->isItemRejected()) {
            return false;
        }

        return $this->trackList[$this->getItemPosition()][self::TRACK_BONUS_STATUS];
    }

    public function getTrackLength(): int
    {
        if ($this->isItemRejected()) {
            return 0;
        }

        return $this->trackList[$this->getItemPosition()][self::TRACK_LENGTH];
    }
}
