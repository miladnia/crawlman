<?php

/*
 * This file is part of the Chakavang package.
 *
 * (c) Milad Nia <milad@miladnia.ir>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Chakavang\Crawlman\Util;

final class UriUtil
{
    public static function isProtocol(string $url, array $protocols): bool
    {
        foreach ($protocols as $p) {
            if (TextUtil::startsWith($url, $p . "://")) {
                return true;
            }
        }

        return false;
    }

    public static function isHttp(string $url): bool
    {
        return self::isProtocol($url, ["http", "https"]);
    }

    public static function exportFragmentIdentifier(string $url): ?string
    {
        $pos = mb_strpos($url, '#');
        if (false === $pos) {
            return null;
        }

        return mb_substr($url, $pos + 1);
    }

    /**
     * Check if the url is http and has file name with extension.
     */
    public static function exportFileName(string $url): ?string
    {
        $matched = preg_match(
            "/^(http|https):\/\/.+\/([^\/]+\.[a-z0-9]+)$/i",
            $url, $matches);

        if (1 !== $matched) {
            return null;
        }

        return $matches[2];
    }

    /**
     * Returns true if entered string is encoded with urlencode() function.
     */
    public static function isEncoded(string $str): bool
    {
        return $str !== urldecode($str);
    }

    /**
     * Encode query of the query.
     *
     * @param string $url Whole url.
     * @param string $query Query to encode.
     * @return string Encoded url.
     */
    public static function encodeUrlQuery(string $url, string $query): string
    {
        return str_replace($query, urlencode($query), $url);
    }

    public static function exportEmailLocalPart(string $email): string
    {
        $pos = \strpos($email, '@');
        return \substr($email, 0, (false === $pos) ? null : $pos);
    }
}
