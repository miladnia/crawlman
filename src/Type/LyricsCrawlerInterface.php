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

interface LyricsCrawlerInterface extends CrawlerInterface
{
    public function getOtherLanguage(string $langCode): ?LyricsCrawlerInterface;

    public function getLyricsContent(): ?string;

    public function getLyricsSourceTitle(): ?string;

    public function getLyricsSourceUrl(): ?string;

    /**
     * @return string[]
     */
    public function getLyricsSongwriters(): array;

    public function getLyricsCopyrightText(): ?string;
}
