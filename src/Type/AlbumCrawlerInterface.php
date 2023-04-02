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

use Chakavang\Crawlman\ListCrawlerInterface;

interface AlbumCrawlerInterface extends ListCrawlerInterface
{
    public function getOtherLanguage(string $langCode): ?AlbumCrawlerInterface;

    public function getAlbumTitle(): ?string;

    public function getAlbumYear(): int;

    public function getAlbumReleaseDate(): ?\DateTimeInterface;

    public function getAlbumCoverAddress(): ?string;

    public function getTrackNumber(): int;

    public function getTrackTitle(): ?string;

    /**
     * @return string[]
     */
    public function getTrackWriters(): array;

    /**
     * @return string[]
     */
    public function getTrackProducers(): array;

    /**
     * @return string[]
     */
    public function getTrackComposers(): array;

    /**
     * @return string[]
     */
    public function getTrackArrangers(): array;

    /**
     * @return string[]
     */
    public function getTrackFeaturedArtists(): array;

    /**
     * @return string[]
     */
    public function getTrackLabels(): array;

    public function getTrackBonusStatus(): bool;

    /**
     * Returns seconds.
     */
    public function getTrackLength(): int;
}
