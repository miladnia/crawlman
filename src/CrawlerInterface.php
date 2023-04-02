<?php

/*
 * This file is part of the Chakavang package.
 *
 * (c) Milad Nia <milad@miladnia.ir>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chakavang\Crawlman;

interface CrawlerInterface
{
    public function getOtherLanguage(string $langCode);
}
