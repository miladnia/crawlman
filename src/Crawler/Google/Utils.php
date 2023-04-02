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

final class Utils
{
    public static function matchUrl(string $url): bool
    {
        $result = preg_match("/^https?:\/\/(www\.)?google\.[a-z]+\//i", $url);
        return 1 === $result;
    }

    public static function encodeQueryValue(string $str): string
    {
        return urlencode(str_replace(' ', '+', $str));
    }
}
