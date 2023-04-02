<?php

/*
 * This file is part of the Chakavang package.
 *
 * (c) Milad Nia <milad@miladnia.ir>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chakavang\Crawlman\Type;

use Chakavang\Crawlman\CrawlerInterface;

interface ArtistCrawlerInterface extends CrawlerInterface
{
    public function getOtherLanguage(string $langCode): ?ArtistCrawlerInterface;

    public function getArtistPhotoAddress(): ?string;

    public function getArtistName(): ?string;

    public function getArtistRealName(): ?string;

    public function getArtistNationality(): ?string;

    public function getArtistBirthPlace(): ?string;

    public function getArtistBirthday(): ?\DateTimeInterface;

    public function getArtistDeathDate(): ?\DateTimeInterface;

    public function getArtistActiveYearsFrom(): int;

    public function getArtistActiveYearsTo(): int;

    /**
     * @return string[]
     */
    public function getArtistOccupations(): array;

    /**
     * @return string[]
     */
    public function getArtistInstruments(): array;

    /**
     * @return string[]
     */
    public function getArtistGenres(): array;

    public function getArtistWebsiteAddress(): ?string;
}
