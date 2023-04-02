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

use Chakavang\Crawlman\Util\TextUtil;

final class Utils
{
    /**
     * Check if url is valid or not.
     */
    public static function matchUrl(string $url): bool
    {
        $result = preg_match("/^https?:\/\/[a-z]+\.wikipedia\.org\//i", $url);
        return 1 === $result;
    }

    public static function encodeQueryValue(string $str): string
    {
        return urlencode(str_replace(' ', '_', $str));
    }

    /**
     * Check query. Returns true if query starts with /wiki/.
     */
    public static function isWikiQuery(string $query): bool
    {
        return TextUtil::startsWith($query, "/wiki/");
    }

    /**
     * Convert a plain text to wiki query.
     */
    public static function makeWikiQuery(string $text): string
    {
        return "/wiki/" . self::encodeQueryValue($text);
    }

    /**
     * Concat query and language code to build an absolute url.
     */
    public static function queryToUrl(string $query, string $langCode): string
    {
        return "https://"
            . $langCode
            . ".wikipedia.org"
            . $query;
    }

    /**
     * Same as queryToUrl() method, but for array data which
     * has wiki query that defined as array key.
     */
    public static function queryToUrlArray(string $langCode, array $array): array
    {
        $modifiedArray = [];

        foreach ($array as $key => $value) {
            if (is_string($key)
                && self::isWikiQuery($key)) {
                $key = self::queryToUrl($key, $langCode);
                $modifiedArray[$key] = $value;
                continue;
            }
            $modifiedArray[] = $value;
        }

        return $modifiedArray;
    }

    /**
     * Convert thumbnail image url to original image url.
     */
    public static function thumbUrlToOriginal(string $url): string
    {
        if (false === strpos($url, "/thumb/")) {
            return $url;
        }

        $url = str_replace("/thumb", '', $url);
        return mb_substr($url, 0, mb_strrpos($url, '/', -1));
    }

    public static function isRegisteredLink(\DOMElement $link): bool
    {
        return "new" !== $link->getAttribute("class");
    }

    public static function exportValidUrl(?\DOMElement $link): ?string
    {
        if ((null === $link)
            || !self::isRegisteredLink($link)) {
            return null;
        }

        $url = $link->getAttribute("href");

        return self::isWikiQuery($url)
            ? $url
            : null;
    }
}
