<?php

namespace Chakavang\Crawlman\Tests\Util;

use PHPUnit\Framework\TestCase;
use Chakavang\Crawlman\Util\TextUtil;

class TextUtilTest extends TestCase
{

    public function testConvertPersianNumbers()
    {
        $converted = TextUtil::convertPersianNumbers("۰ ۱ ۲ ۳ ۴ ۵ ۶ ۷ ۸ ۹");
        $this->assertEquals("0 1 2 3 4 5 6 7 8 9", $converted);

        $converted = TextUtil::convertPersianNumbers("۲۳ اردیبهشت ۱۳۶۴");
        $this->assertEquals("23 اردیبهشت 1364", $converted);

        $converted = TextUtil::convertPersianNumbers("٢٣ خرداد ۱۳۲۵");
        $this->assertEquals("23 خرداد 1325", $converted);
    }

    public function testGetLine()
    {
        $text = <<<TEST
First line
2nd line
3rd line
TEST;

        $this->assertEquals("First line", TextUtil::getLine(-3, $text));
        $this->assertEquals("First line", TextUtil::getLine(0, $text));
        $this->assertEquals("First line", TextUtil::getLine(1, $text));
        $this->assertEquals("2nd line", TextUtil::getLine(2, $text));
        $this->assertEquals("3rd line", TextUtil::getLine(3, $text));

    }

    public function testNormalizedHtmlSpaces()
    {
        $text = TextUtil::normalizedHtmlSpaces("Years active");
        $this->assertEquals("Years active", $text);

        $text = TextUtil::normalizedHtmlSpaces("واژه فارسی");
        $this->assertEquals("واژه فارسی", $text);
    }

    public function testExtractAfter()
    {
        $text = 'hey boys whats up?';
        $this->assertEquals('whats up?', TextUtil::extractAfter(' boys ', $text));
        $this->assertEquals(' boys whats up?', TextUtil::extractAfter('hey', $text));
        $this->assertEquals('hey boys whats up?', TextUtil::extractAfter('john', $text));
        $this->assertEquals('billie', TextUtil::extractAfter('john', $text, 'billie'));
    }

    public function testContains()
    {
        $text = "How are you today?";
        $this->assertEquals(true, TextUtil::contains('you', $text));
        $this->assertEquals(false, TextUtil::contains('me', $text));
    }
}
