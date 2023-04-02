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

use Morilog\Jalali\CalendarUtils;
use Morilog\Jalali\Jalalian;

final class DateTimeUtil
{
    const YEARS_DISTANCE = 120;
    const DEFAULT_DATE_FORMAT = "F j, Y";

    private static function generateYearList(int $max, int $distance, bool $assoc): array
    {
        $years = array_reverse(range($max - $distance, $max));
        return $assoc ? array_combine($years, $years) : $years;
    }

    public static function getYears(int $yearsNumber = self::YEARS_DISTANCE, bool $assoc = true): array
    {
        return self::generateYearList((int)date('Y'), $yearsNumber, $assoc);
    }

    public static function getSolarHijriYears(int $yearsNumber = self::YEARS_DISTANCE, bool $assoc = true): array
    {
        return self::generateYearList((int)Jalalian::now()->getYear(), $yearsNumber, $assoc);
    }

    /**
     * Simple calendar specification.
     */
    public static function isSolarHijriYear(int $year): bool
    {
        return (1200 < $year) && (1500 > $year);
    }

    /**
     * Converts human readable time HH:MM:SS to seconds.
     */
    public static function timeToSeconds(string $time): int
    {
        $pieces = array_reverse(explode(':', $time));

        // If format is not valid.
        if (3 < count($pieces)) {
            return 0;
        }

        // Fill empty hours and empty minutes with 0.
        for ($i = 0; $i < 3 - count($pieces); $i++) {
            $pieces[] = 0;
        }

        list($seconds, $minutes, $hours) = $pieces;
        $minutes += ((int)$hours) * 60;
        $seconds += ((int)$minutes) * 60;

        return (int)$seconds;
    }

    public static function solarHijriMonthToNumber(string $monthName): int
    {
        $monthList = [
            1 => "فروردین",
            2 => "اردیبهشت",
            3 => "خرداد",
            4 => "تیر",
            5 => "مرداد",
            6 => "شهریور",
            7 => "مهر",
            8 => "آبان",
            9 => "آذر",
            10 => "دی",
            11 => "بهمن",
            12 => "اسفند"
        ];

        $result = array_search($monthName, $monthList, true);

        return (false === $result) ? 0 : (int) $result;
    }

    public static function resolveDate(int $year, string $month, int $day, bool $solarHijri = false): ?\DateTimeInterface
    {
        if ($solarHijri) {
            $month = is_numeric($month) ? (int) $month
                : self::solarHijriMonthToNumber($month);
            return CalendarUtils::toGregorianDate($year, $month, $day);
        }

        if (!is_numeric($month)) {
            // Look for January through December.
            $tempDate = \DateTime::createFromFormat('F', $month);
            if (false === $tempDate) {
                // Look for Jan through Dec.
                $tempDate = \DateTime::createFromFormat('M', $month);
                if (false === $tempDate) {
                    return null;
                }
            }

            // Convert to number.
            $month = $tempDate->format('n');
        }

        return (new \DateTime())->setDate($year, (int) $month, $day);
    }

    /**
     * @param string $date Formatted Solar Hijri date (example: ۱ فروردین ۱۳۳۳).
     */
    public static function resolveFormattedDate(string $date, bool $solarHijri, ?string $format = null): ?\DateTimeInterface
    {
        if (!$solarHijri) {
            $datetime = \DateTime::createFromFormat($format ?? self::DEFAULT_DATE_FORMAT, $date);
            return (false !== $datetime) ? $datetime : null;
        }

        $date = TextUtil::convertNumbersToLatin($date);

        if (null !== $format) {
            return CalendarUtils::createDatetimeFromFormat($format, $date);
        }

        $dateList = explode(' ', $date);
        if (3 !== count($dateList)) {
            return null;
        }
        [$day, $monthName, $year] = $dateList;
        $month = self::solarHijriMonthToNumber($monthName);
        return CalendarUtils::toGregorianDate((int)$year, (int)$month, (int)$day);
    }
}
