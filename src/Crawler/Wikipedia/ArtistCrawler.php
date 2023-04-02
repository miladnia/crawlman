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

use Chakavang\Crawlman\Util\CrawlUtil;
use Chakavang\Crawlman\Util\TextUtil;
use Chakavang\Crawlman\Type\ArtistCrawlerInterface;
use Symfony\Component\DomCrawler\Crawler;

final class ArtistCrawler extends AbstractCrawler implements ArtistCrawlerInterface
{
    const LABEL_BIRTH_NAME = "birth-name";
    const LABEL_STAGE_NAME = "stage-name";
    const LABEL_NATIONALITY = "nationality";
    const LABEL_BORN = "born";
    const LABEL_DIED = "died";
    const LABEL_ACTIVE_YEARS = "years-active";
    const LABEL_OCCUPATION = "occupation";
    const LABEL_GENRE = "genre";
    const LABEL_INSTRUMENT = "instrument";
    const LABEL_WEBSITE = "website";

    /**
     * @var string
     */
    private $artistPhotoAddress;

    /**
     * @var string
     */
    private $artistName;

    /**
     * @var string
     */
    private $artistRealName;

    /**
     * @var string
     */
    private $artistNationality;

    /**
     * @var string
     */
    private $artistBirthPlace;

    /**
     * @var \DateTimeInterface
     */
    private $artistBirthday;

    /**
     * @var \DateTimeInterface
     */
    private $artistDeathDate;

    /**
     * @var int
     */
    private $artistActiveYearsFrom;

    /**
     * @var int
     */
    private $artistActiveYearsTo;

    /**
     * @var array
     */
    private $artistOccupations;

    /**
     * @var array
     */
    private $artistGenres;

    /**
     * @var array
     */
    private $artistInstruments;

    /**
     * @var int
     */
    private $artistWebsiteAddress;

    protected function crawl(Crawler $crawler, ?string $fragmentId = null): void
    {
        $dictionary = $this->getDictionary($this->getLangCode());
        $this->artistPhotoAddress = $this->findMainImageAddress();
        $this->artistName = $this->readInfoBoxText($dictionary[self::LABEL_STAGE_NAME]) ?? $this->readArticleTitle();
        $this->crawlRealName();
        $this->artistNationality = $this->readInfoBoxText($dictionary[self::LABEL_NATIONALITY]);
        $this->crawlBirthPlace();
        $this->artistBirthday = $this->readInfoBoxDate($dictionary[self::LABEL_BORN]);
        $this->artistDeathDate = $this->readInfoBoxDate($dictionary[self::LABEL_DIED]);
        $activeYears = $this->readInfoBoxDateRange($dictionary[self::LABEL_ACTIVE_YEARS]);
        $this->artistActiveYearsFrom = $activeYears["from"];
        $this->artistActiveYearsTo = $activeYears["to"];
        $this->artistOccupations = $this->readInfoBoxTextList($dictionary[self::LABEL_OCCUPATION]);
        $this->artistGenres = $this->readInfoBoxTextList($dictionary[self::LABEL_GENRE]);
        $this->artistInstruments = $this->readInfoBoxTextList($dictionary[self::LABEL_INSTRUMENT]);
        $this->artistWebsiteAddress = $this->readInfoBoxLink($dictionary[self::LABEL_WEBSITE]);
    }

    private function crawlBirthPlace(): void
    {
        $dictionary = $this->getDictionary($this->getLangCode());
        $birthRow = $this->readInfoBoxRow($dictionary[self::LABEL_BORN]);

        if (null === $birthRow) {
            return;
        }

        $birthPlaceElement = $birthRow->filter(".birthplace");
        $this->artistBirthPlace = (0 !== \count($birthPlaceElement))
            ? $birthPlaceElement->text()
            : TextUtil::removeBrackets(CrawlUtil::readLastLine($birthRow));
    }

    private function crawlRealName(): void
    {
        $dictionary = $this->getDictionary($this->getLangCode());

        $birthName = $this->readInfoBoxText($dictionary[self::LABEL_BIRTH_NAME]);
        if (null !== $birthName) {
            $this->artistRealName = $this->readInfoBoxText($dictionary[self::LABEL_BIRTH_NAME]);
            return;
        }

        if (!$this->isLang(self::LANG_PERSIAN)) {
            $bornRow = $this->readInfoBoxRow($dictionary[self::LABEL_BORN]);
            if (null != $bornRow) {
                $this->artistRealName = CrawlUtil::readFirstLine($bornRow);
                return;
            }
        }

        $this->artistRealName = $this->readInfoBoxTitle();
    }

    private function getDictionary(string $langCode): array
    {
        $dictionary = [
            "en" => [
                self::LABEL_BIRTH_NAME => ["Birth name"],
                self::LABEL_STAGE_NAME => ["Stage name"], // Not approved.
                self::LABEL_NATIONALITY => ["Nationality"], // Not approved.
                self::LABEL_BORN => ["Born"],
                self::LABEL_DIED => ["Died"],
                self::LABEL_ACTIVE_YEARS => ["Years active", "Years active"], // The first element has html special space.
                self::LABEL_OCCUPATION => ["Occupation", "Occupations", "Occupation(s)"],
                self::LABEL_GENRE => ["Genre", "Genres", "Genre(s)"],
                self::LABEL_INSTRUMENT => ["Instrument", "Instruments", "Instrument(s)"],
                self::LABEL_WEBSITE => ["Website"],
            ],
            "fa" => [
                self::LABEL_BIRTH_NAME => ["نام", "نام اصلی"],
                self::LABEL_STAGE_NAME => ["نام مستعار"],
                self::LABEL_NATIONALITY => ["ملیت"],
                self::LABEL_BORN => ["تولد", "زادروز", "زاده"],
                self::LABEL_DIED => ["مرگ"],
                self::LABEL_ACTIVE_YEARS => ["سال‌های فعالیت"],
                self::LABEL_OCCUPATION => ["پیشه", "پیشه‌ها", "پیشه(ها)", "پیشه‌(ها)",
                    "شغل", "شغل‌ها", "شغل(ها)", "شغل‌(ها)",
                    "حرفه", "حرفه‌ها", "حرفه(ها)", "حرفه‌(ها)"], // Some terms have different type of spaces.
                self::LABEL_GENRE => ["سبک", "سبک‌ها", "سبک(ها)", "سبک‌(ها)"],
                self::LABEL_INSTRUMENT => ["ساز", "سازها", "ساز(ها)", "ساز‌(ها)"],
                self::LABEL_WEBSITE => ["وبگاه"],
            ],
        ];

        return $dictionary[$langCode] ?? $dictionary["en"];
    }

    public function getOtherLanguage(string $langCode): ?ArtistCrawlerInterface
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

    public function getArtistPhotoAddress(): ?string
    {
        return $this->artistPhotoAddress;
    }

    public function getArtistName(): ?string
    {
        return $this->artistName;
    }

    public function getArtistRealName(): ?string
    {
        return $this->artistRealName;
    }

    public function getArtistNationality(): ?string
    {
        return $this->artistNationality;
    }

    public function getArtistBirthPlace(): ?string
    {
        return $this->artistBirthPlace;
    }

    public function getArtistBirthday(): ?\DateTimeInterface
    {
        return $this->artistBirthday;
    }

    public function getArtistDeathDate(): ?\DateTimeInterface
    {
        return $this->artistDeathDate;
    }

    public function getArtistActiveYearsFrom(): int
    {
        return $this->artistActiveYearsFrom;
    }

    public function getArtistActiveYearsTo(): int
    {
        return $this->artistActiveYearsTo;
    }

    public function getArtistOccupations(): array
    {
        return $this->artistOccupations;
    }

    public function getArtistGenres(): array
    {
        return $this->artistGenres;
    }

    public function getArtistInstruments(): array
    {
        return $this->artistInstruments;
    }

    public function getArtistWebsiteAddress(): ?string
    {
        return $this->artistWebsiteAddress;
    }
}
