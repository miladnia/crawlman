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

use Symfony\Component\DomCrawler\Crawler;

final class CrawlUtil
{
    /**
     * Find first single text.
     */
    public static function findFirstTextNode(\DOMNode $element): ?string
    {
        foreach ($element->childNodes as $node) {
            if ($node instanceof \DOMText) {
                return $node->nodeValue;
            }
        }

        return null;
    }

    /**
     * Returns everything before the first <br> tag.
     */
    public static function readFirstLine(Crawler $crawler): ?string
    {
        $brElements = $crawler->filter("br");

        if (0 === \count($brElements)) {
            return null;
        }

        $node = $brElements->first()->getNode(0);
        $line = '';
        while (null !== $node->previousSibling) {
            $line .= $node->previousSibling->nodeValue;
            $node = $node->previousSibling;
        }

        return $line;

        // return $brElement->getNode(0)->previousSibling->nodeValue;
    }

    /**
     * Returns everything after the last <br> tag.
     */
    public static function readLastLine(Crawler $crawler): ?string
    {
        $brElements = $crawler->filter("br");

        if (0 === \count($brElements)) {
            return null;
        }

        $node = $brElements->last()->getNode(0);
        $line = '';
        while (null !== $node->nextSibling) {
            $line .= $node->nextSibling->nodeValue;
            $node = $node->nextSibling;
        }

        return $line;
    }
}
