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

final class TextUtil
{
    /**
     * Combination of these methods:
     * - normalizePersianChars()
     * - removeIllegalChars()
     * - normalizeSpaces()
     */
    public static function clean(?string $text, bool $multilineText = false): ?string
    {
        if (null === $text) {
            return null;
        }

        $text = self::normalizePersianChars($text);
        $text = self::removeIllegalChars($text);
        $text = self::normalizeSpaces($text, $multilineText);

        return $text;
    }

    /**
     * Returns a normalized full latin text.
     */
    public static function makeAbsoluteLatin(?string $text): ?string
    {
        if (null === $text) {
            return null;
        }

        $text = TextUtil::normalizeLatinChars($text);
        $text = TextUtil::removeExceptLatin($text);
        $text = TextUtil::normalizeSpaces($text);

        return $text;
    }

    /**
     * Remove all illegal characters.
     */
    public static function removeIllegalChars(?string $text): ?string
    {
        if (null === $text) {
            return null;
        }

        return \preg_replace("/[^\p{Latin}\p{Old_Persian}\p{Arabic}a-zA-Z0-9 ]+/u", ' ', $text);
    }

    /**
     * Remove everything except latin alphabets, numbers and space char.
     */
    public static function removeExceptLatin(?string $text): ?string
    {
        if (null === $text) {
            return null;
        }

        return \preg_replace("/[^a-zA-Z0-9 ]+/", ' ', $text);
    }

    public static function removeBrackets(?string $text): ?string
    {
        if (null === $text) {
            return null;
        }

        return \preg_replace("/\[[^\]]*\]/", '', $text);
    }

    public static function removeBracketsArray(array $textArray): array
    {
        $newText = [];

        foreach ($textArray as $key => $text) {
            $newText[$key] = self::removeBrackets($text);
        }

        return $newText;
    }

    public static function removeParentheses(?string $text, ?callable $callback = null): ?string
    {
        if (null === $text) {
            return null;
        }

        if (null !== $callback) {
            $result = \preg_match_all("/\(([^\)]*)\)/", $text, $matches);

            if ((false === $result)
                || (0 === $result)) {
                return $text;
            }

            foreach ($matches[1] as $textPart) {
                $callback($textPart);
            }
        }

        return \preg_replace("/\([^\)]*\)/", '', $text);
    }

    public static function removeNumbersInsideParentheses(?string $text, ?callable $callback = null): ?string
    {
        if (null === $text) {
            return null;
        }

        $modifiedText = TextUtil::convertNumbersToLatin($text);

        if (null !== $callback) {
            $numberCounter = \preg_match_all("/\(([0-9]+)\)/", $modifiedText, $matches);

            if ((false === $numberCounter)
                || (0 === $numberCounter)) {
                return $text;
            }

            foreach ($matches[1] as $number) {
                $callback((int) $number);
            }
        }

        return \preg_replace("/\([0-9]+\)/", '', $modifiedText);
    }

    /**
     * Covert spaces to dash character.
     */
    public static function makeDashed(?string $text): ?string
    {
        if (null === $text) {
            return null;
        }

        // Replace all spaces with dash character.
        $text = \preg_replace("/\s+/", '-', $text);
        // Remove repetitive dash characters.
        $text = \preg_replace("/[\-]{2,}/", '-', $text);
        // Remove extra dash characters.
        $text = \trim($text, '-');

        return $text;
    }

    public static function trimArray(array $text): array
    {
        foreach ($text as $k => $v) {
            $text[$k] = \trim($v);
        }

        return $text;
    }

    public static function explode(string $text, ?callable $callback = null): array
    {
        $delimiters = [',', '،', '·'];
        $words = [',' => ' and ', '،' => ' و '];
        $textList = [];

        foreach ($delimiters as $d) {
            if (false !== \mb_strpos($text, $d)) {
                $textList = \explode($d, $text);
                if (\array_key_exists($d, $words)) {
                    $lastKey = \array_key_last($textList);
                    $lastValue = $textList[$lastKey];
                    unset($textList[$lastKey]);
                    $textList = \array_merge($textList, \explode($words[$d], $lastValue));
                }
                break;
            }
        }

        if (empty($textList)) {
            foreach ($words as $w) {
                if (false !== \mb_strpos($text, $w)) {
                    $textList = \explode($w, $text);
                    break;
                }
            }
        }

        if (empty($textList)) {
            $textList[] = $text;
        }

        foreach ($textList as $key => $text) {
            $text = \trim($text);
            if (null !== $callback) {
                $text = $callback($text);
            }
            $textList[$key] = $text;
        }

        return $textList;
    }

    public static function startsWith($text, $startsWith): bool
    {
        return 0 === \mb_strpos($text, $startsWith);
    }

    public static function getLine(int $lineNumber, string $text): ?string
    {
        if ($lineNumber < 1) {
            $lineNumber = 1;
        }

        $text = \str_replace("\r", "\n", $text);
        $text = \preg_replace(['/\n+/'], ["\n"], $text);
        $lines = \explode("\n", $text, 1 + $lineNumber);

        return $lines[--$lineNumber] ?? null;
    }

    public static function getFirstLine(string $text): ?string
    {
        return self::getLine(1, $text);
    }

    public static function toLower(array $textList): array
    {
        foreach ($textList as $key => $text) {
            $textList[$key] = \mb_strtolower($text);
        }

        return $textList;
    }

    public static function makeTag(string $text): ?string
    {
        return \mb_strtolower(
            self::clean(self::normalizePersianSpaces($text)));
    }

    /**
     * Returns false if the entered text has non-ASCII characters.
     */
    private static function isASCII(string $text): string
    {
        // '\x80-\xFF' refers to a character range outside ASCII.
        return 1 !== preg_match('/[\x80-\xff]/', $text);
    }

    public static function convertNumbersToLatin(?string $text): ?string
    {
        if (null === $text) {
            return null;
        }

        return self::convertPersianNumbers($text);
    }

    public static function convertPersianNumbers(?string $text): ?string
    {
        if (null === $text) {
            return null;
        }

        $persianNumbers =[
            // ARABIC-INDIC DIGIT
            [
                0 => '٠', 1 => '١',
                2 => '٢', 3 => '٣',
                4 => '٤', 5 => '٥',
                6 => '٦', 7 => '٧',
                8 => '٨', 9 => '٩',
            ],
            // EXTENDED ARABIC-INDIC DIGIT
            [
                0 => '۰', 1 => '۱',
                2 => '۲', 3 => '۳',
                4 => '۴', 5 => '۵',
                6 => '۶', 7 => '۷',
                8 => '۸', 9 => '۹',
            ],
        ];

        foreach ($persianNumbers as $numbers) {
            $text = \str_replace($numbers, \array_keys($numbers), $text);
        }

        return $text;
    }

    public static function normalizedHtmlSpaces(?string $text): ?string
    {
        if (null === $text) {
            return null;
        }

        return \str_replace(self::htmlSpace(), ' ', $text);
    }

    public static function htmlSpace(): string
    {
        // UTF encodes "&nbsp;" as chr(0xC2).chr(0xA0)
        return \chr(0xC2).\chr(0xA0);
    }

    /**
     * Validate and convert illegal persian characters.
     */
    public static function normalizePersianChars(string $text): string
    {
        if (self::isASCII($text)) {
            return $text;
        }

        $charMap = [
            "ك" => "ک",
            "ي" => "ی",
        ];

        return \str_replace(\array_keys($charMap), $charMap, $text);
    }

    public static function normalizePersianSpaces(?string $text): ?string
    {
        if (null === $text) {
            return null;
        }

        // Convert persian half-space chars to normal space char.
        return \preg_replace("/‌/", ' ', $text);
    }

    public static function normalizeSpaces(?string $text, bool $multilineText = false): ?string
    {
        if (null === $text) {
            return null;
        }

        $text = \trim($text);
        if ($multilineText) {
            $text = \str_replace("\r", "\n", $text);
            $text = \preg_replace(['/\n+/', '/[ \t]+/'], ["\n", ' '], $text);
        } else {
            $text = \preg_replace('/\s+/', ' ', $text);
        }

        return $text;
    }

    public static function normalizeLatinChars(?string $text): ?string
    {
        if ((null === $text) || self::isASCII($text)) {
            return $text;
        }

        $charMap = [
            // Decompositions for Latin-1 Supplement.
            'ª' => 'a', 'º' => 'o', 'À' => 'A', 'Á' => 'A', 'Â' => 'A',
            'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
            'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I',
            'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ð' => 'D', 'Ñ' => 'N',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y',
            'Þ' => 'TH', 'ß' => 's', 'à' => 'a', 'á' => 'a', 'â' => 'a',
            'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
            'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i',
            'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ð' => 'd', 'ñ' => 'n',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o',
            'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u',
            'ý' => 'y', 'þ' => 'th', 'ÿ' => 'y', 'Ø' => 'O',
            // Decompositions for Latin Extended-A.
            'Ā' => 'A', 'ā' => 'a', 'Ă' => 'A', 'ă' => 'a', 'Ą' => 'A',
            'ą' => 'a', 'Ć' => 'C', 'ć' => 'c', 'Ĉ' => 'C', 'ĉ' => 'c',
            'Ċ' => 'C', 'ċ' => 'c', 'Č' => 'C', 'č' => 'c', 'Ď' => 'D',
            'ď' => 'd', 'Đ' => 'D', 'đ' => 'd', 'Ē' => 'E', 'ē' => 'e',
            'Ĕ' => 'E', 'ĕ' => 'e', 'Ė' => 'E', 'ė' => 'e', 'Ę' => 'E',
            'ę' => 'e', 'Ě' => 'E', 'ě' => 'e', 'Ĝ' => 'G', 'ĝ' => 'g',
            'Ğ' => 'G', 'ğ' => 'g', 'Ġ' => 'G', 'ġ' => 'g', 'Ģ' => 'G',
            'ģ' => 'g', 'Ĥ' => 'H', 'ĥ' => 'h', 'Ħ' => 'H', 'ħ' => 'h',
            'Ĩ' => 'I', 'ĩ' => 'i', 'Ī' => 'I', 'ī' => 'i', 'Ĭ' => 'I',
            'ĭ' => 'i', 'Į' => 'I', 'į' => 'i', 'İ' => 'I', 'ı' => 'i',
            'Ĳ' => 'IJ', 'ĳ' => 'ij', 'Ĵ' => 'J', 'ĵ' => 'j', 'Ķ' => 'K',
            'ķ' => 'k', 'ĸ' => 'k', 'Ĺ' => 'L', 'ĺ' => 'l', 'Ļ' => 'L',
            'ļ' => 'l', 'Ľ' => 'L', 'ľ' => 'l', 'Ŀ' => 'L', 'ŀ' => 'l',
            'Ł' => 'L', 'ł' => 'l', 'Ń' => 'N', 'ń' => 'n', 'Ņ' => 'N',
            'ņ' => 'n', 'Ň' => 'N', 'ň' => 'n', 'ŉ' => 'n', 'Ŋ' => 'N',
            'ŋ' => 'n', 'Ō' => 'O', 'ō' => 'o', 'Ŏ' => 'O', 'ŏ' => 'o',
            'Ő' => 'O', 'ő' => 'o', 'Œ' => 'OE', 'œ' => 'oe', 'Ŕ' => 'R',
            'ŕ' => 'r', 'Ŗ' => 'R', 'ŗ' => 'r', 'Ř' => 'R', 'ř' => 'r',
            'Ś' => 'S', 'ś' => 's', 'Ŝ' => 'S', 'ŝ' => 's', 'Ş' => 'S',
            'ş' => 's', 'Š' => 'S', 'š' => 's', 'Ţ' => 'T', 'ţ' => 't',
            'Ť' => 'T', 'ť' => 't', 'Ŧ' => 'T', 'ŧ' => 't', 'Ũ' => 'U',
            'ũ' => 'u', 'Ū' => 'U', 'ū' => 'u', 'Ŭ' => 'U', 'ŭ' => 'u',
            'Ů' => 'U', 'ů' => 'u', 'Ű' => 'U', 'ű' => 'u', 'Ų' => 'U',
            'ų' => 'u', 'Ŵ' => 'W', 'ŵ' => 'w', 'Ŷ' => 'Y', 'ŷ' => 'y',
            'Ÿ' => 'Y', 'Ź' => 'Z', 'ź' => 'z', 'Ż' => 'Z', 'ż' => 'z',
            'Ž' => 'Z', 'ž' => 'z', 'ſ' => 's',
            // Decompositions for Latin Extended-B.
            'Ș' => 'S', 'ș' => 's', 'Ț' => 'T', 'ț' => 't',
            // Euro sign.
            '€' => 'E',
            // GBP (Pound) sign.
            '£' => '',
            // Vowels with diacritic (Vietnamese).
            // Unmarked.
            'Ơ' => 'O', 'ơ' => 'o', 'Ư' => 'U', 'ư' => 'u',
            // Grave accent.
            'Ầ' => 'A', 'ầ' => 'a', 'Ằ' => 'A', 'ằ' => 'a', 'Ề' => 'E',
            'ề' => 'e', 'Ồ' => 'O', 'ồ' => 'o', 'Ờ' => 'O', 'ờ' => 'o',
            'Ừ' => 'U', 'ừ' => 'u', 'Ỳ' => 'Y', 'ỳ' => 'y',
            // Hook.
            'Ả' => 'A', 'ả' => 'a', 'Ẩ' => 'A', 'ẩ' => 'a', 'Ẳ' => 'A',
            'ẳ' => 'a', 'Ẻ' => 'E', 'ẻ' => 'e', 'Ể' => 'E', 'ể' => 'e',
            'Ỉ' => 'I', 'ỉ' => 'i', 'Ỏ' => 'O', 'ỏ' => 'o', 'Ổ' => 'O',
            'ổ' => 'o', 'Ở' => 'O', 'ở' => 'o', 'Ủ' => 'U', 'ủ' => 'u',
            'Ử' => 'U', 'ử' => 'u', 'Ỷ' => 'Y', 'ỷ' => 'y',
            // Tilde.
            'Ẫ' => 'A', 'ẫ' => 'a', 'Ẵ' => 'A', 'ẵ' => 'a', 'Ẽ' => 'E',
            'ẽ' => 'e', 'Ễ' => 'E', 'ễ' => 'e', 'Ỗ' => 'O', 'ỗ' => 'o',
            'Ỡ' => 'O', 'ỡ' => 'o', 'Ữ' => 'U', 'ữ' => 'u', 'Ỹ' => 'Y',
            'ỹ' => 'y',
            // Acute accent.
            'Ấ' => 'A', 'ấ' => 'a', 'Ắ' => 'A', 'ắ' => 'a', 'Ế' => 'E',
            'ế' => 'e', 'Ố' => 'O', 'ố' => 'o', 'Ớ' => 'O', 'ớ' => 'o',
            'Ứ' => 'U', 'ứ' => 'u',
            // Dot below.
            'Ạ' => 'A', 'ạ' => 'a', 'Ậ' => 'A', 'ậ' => 'a', 'Ặ' => 'A',
            'ặ' => 'a', 'Ẹ' => 'E', 'ẹ' => 'e', 'Ệ' => 'E', 'ệ' => 'e',
            'Ị' => 'I', 'ị' => 'i', 'Ọ' => 'O', 'ọ' => 'o', 'Ộ' => 'O',
            'ộ' => 'o', 'Ợ' => 'O', 'ợ' => 'o', 'Ụ' => 'U', 'ụ' => 'u',
            'Ự' => 'U', 'ự' => 'u', 'Ỵ' => 'Y', 'ỵ' => 'y',
            // Vowels with diacritic (Chinese, Hanyu Pinyin).
            'ɑ' => 'a',
            // Macron.
            'Ǖ' => 'U', 'ǖ' => 'u',
            // Acute accent.
            'Ǘ' => 'U', 'ǘ' => 'u',
            // Caron.
            'Ǎ' => 'A', 'ǎ' => 'a', 'Ǐ' => 'I', 'ǐ' => 'i', 'Ǒ' => 'O',
            'ǒ' => 'o', 'Ǔ' => 'U', 'ǔ' => 'u', 'Ǚ' => 'U', 'ǚ' => 'u',
            // Grave accent.
            'Ǜ' => 'U', 'ǜ' => 'u',
        ];

        return \strtr($text, $charMap);
    }

    /**
     * Returns part of the text after the needle value.
     * If the needle has not found and default value was NULL
     * it would return the whole text.
     *
     * @param null|string $default Set a value to receive on failure. NULL value means the same entered text.
     */
    public static function extractAfter(string $needle, ?string $text, ?string $default = null): ?string
    {
        if (null === $text) {
            return null;
        }

        $pos = \mb_strpos($text, $needle);

        if (false === $pos) {
            return $default ?? $text;
        }

        return \mb_substr($text, $pos + \mb_strlen($needle));
    }

    public static function contains(string $needle, ?string $text): bool
    {
        if (null === $text) {
            return null;
        }

        return false !== \mb_strpos($text, $needle);
    }
}
