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

interface AlbumListCrawlerInterface extends ListCrawlerInterface
{
    public function getOtherLanguage(string $langCode): ?AlbumListCrawlerInterface;

    public function getAlbumUrl(): ?string;

    public function getAlbumName(): ?string;

    public function getAlbumYear(): int;
}
