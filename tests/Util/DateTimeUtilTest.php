<?php

namespace Chakavang\Crawlman\Tests\Util;

use PHPUnit\Framework\TestCase;
use Chakavang\Crawlman\Util\DateTimeUtil;

class DateTimeUtilTest extends TestCase
{
    public function testTimeToSeconds()
    {
        $minTest = "48:09";
        $minResult = DateTimeUtil::timeToSeconds($minTest);
        $this->assertEquals(2889, $minResult, sprintf("min test %s converted to %s seconds.", $minTest, $minResult));

        $hourTest = "14:52:36";
        $hourResult = DateTimeUtil::timeToSeconds($hourTest);
        $this->assertEquals(53556, $hourResult, sprintf("hour test %s converted to %s seconds.", $hourTest, $hourResult));
    }

    public function testResolveFormattedDate()
    {
        $dateStr = "۵ اردیبهشت ۱۳۵۲";
        $dateTime = DateTimeUtil::resolveFormattedDate($dateStr, true);
        $this->assertEquals("1973", $dateTime->format("Y")); // Year
        $this->assertEquals("4", $dateTime->format("n")); // Month
        $this->assertEquals("25", $dateTime->format("j")); // Day
    }
}
