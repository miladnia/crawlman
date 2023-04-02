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

use Chakavang\Crawlman\Util\DateTimeUtil;
use Chakavang\Crawlman\Util\TextUtil;
use Chakavang\Crawlman\Util\XPathUtil;
use Chakavang\Crawlman\CrawlerInterface;;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractCrawler extends \Chakavang\Crawlman\AbstractCrawler
{
    const LANG_DEFAULT = self::LANG_ENGLISH;

    protected function makeUrl(string $query, ?string $langCode = null): string
    {
        $query = Utils::makeWikiQuery($query);
        return Utils::queryToUrl($query, $langCode ?? self::LANG_DEFAULT);
    }

    protected function matchUrl(string $url): bool
    {
        return Utils::matchUrl($url);
    }

    protected function extractUrlLang(string $url): string
    {
        $result = \preg_match("/\/\/([a-z]+)\./", $url, $matches);
        return (1 === $result) ? \mb_strtolower($matches[1]) : self::LANG_DEFAULT;
    }

    protected function extractUrlQuery(string $url): ?string
    {
        $result = \preg_match("/\/([^\/#]+)(#.*)?$/", $url, $matches);
        return (1 === $result) ? $matches[1] : null;
    }

    protected function getCodeName(): string
    {
        return "wikipedia";
    }

    protected function findLanguageLink(string $langCode): ?string
    {
        if ($this->isLang($langCode)) {
            return null;
        }

        $englishLinkNode = $this->getCrawler()
            ->filter('#p-lang li a[hreflang="' . $langCode . '"]')
            ->first();

        return (0 === \count($englishLinkNode)) ? null
            : $englishLinkNode->attr("href");
    }

    protected function readArticleTitle(): string
    {
        $title = $this->getCrawler()
            ->filter("#firstHeading")
            ->first()
            ->text();

        return TextUtil::removeBrackets($title) ?? $title;
    }

    protected function findMainImageAddress(): ?string
    {
        return $this->readInfoBoxPhotoAddress();
    }

    /**
     * Returns the main title of the InfoBox.
     */
    protected function readInfoBoxTitle(): ?string
    {
        $infoBoxHead = $this->getCrawler()
            ->filter(".infobox th")
            ->first();

        if (0 === \count($infoBoxHead)) {
            return null;
        }

        // If there are multiple lines, just select the first one.
        $brElement = $infoBoxHead->filter("br");
        if (0 !== \count($brElement)) {
            return $brElement->getNode(0)
                ->previousSibling->nodeValue;
        }

        return $infoBoxHead->text();
    }

    /**
     * Returns address of the main photo of the InfoBox.
     */
    protected function readInfoBoxPhotoAddress(): ?string
    {
        $imgElement = $this->getCrawler()
            ->filter(".infobox .image img")
            ->first();

        if (0 === \count($imgElement)) {
            return null;
        }

        return Utils::thumbUrlToOriginal(
            $imgElement->image()->getUri());
    }

    protected function readInfoBoxRow(array $label): ?Crawler
    {
        if (empty($label)) {
            return null;
        }

        $infoBox = $this->getCrawler()
            ->filter("table.infobox th[scope=\"row\"]");

        if (0 === \count($infoBox)) {
            $this->logger->notice("Wikipedia Crawler - Label not found.", [
                "label" => $label
            ]);
            return null;
        }

        $labelCondition = XPathUtil::makeSearchCondition($label, "text()") . ' or '
            . XPathUtil::makeSearchCondition($label, "./a/text()") . ' or '
            . XPathUtil::makeSearchCondition($label, "./span/text()") . ' or '
            . XPathUtil::makeSearchCondition($label, "./div/text()");
        $labelNode = $infoBox->filterXPath("//th[{$labelCondition}]");

        if (0 === \count($labelNode)) {
            return null;
        }

        $rowContent = $labelNode
            ->first()
            ->nextAll();

        if (0 === \count($rowContent)) {
            return null;
        }

        return $rowContent;
    }

    protected function readInfoBoxText(array $label, ?string $default = null): ?string
    {
        $row = $this->readInfoBoxRow($label);
        return (null === $row) ? $default : \trim(TextUtil::removeBrackets($row->text()));
    }

    protected function readInfoBoxTextList(array $label): array
    {
        $row = $this->readInfoBoxRow($label);

        if (null === $row) {
            return [];
        }

        $list = $row->filter("li");
        if (0 !== \count($list)) {
            $textList = [];
            // Store text of each <li> item.
            $list->each(function(Crawler $li) use (&$textList) {
                $textList[] = TextUtil::removeBrackets($li->text());
            });

            return $textList;
        }

        $brElements = $row->filter("br");
        if (0 !== \count($brElements)) {
            $textList = [];
            $node = $brElements->first()
                ->getNode(0)
                ->previousSibling;
            // Store all the texts that separated by <br> tag.
            while (null !== $node) {
                $textList[] = $node->nodeValue;
                if (null === $node->nextSibling) {
                    break;
                }
                $node = $node->nextSibling->nextSibling;
            }

            return $textList;
        }

        return TextUtil::explode(TextUtil::removeBrackets($row->text()));
    }

    protected function readInfoBoxLink(array $label): ?string
    {
        $row = $this->readInfoBoxRow($label);

        if (null === $row) {
            return null;
        }

        $link = $row->filter("a")->first();

        return (0 === \count($link)) ? null
            : $link->attr("href");
    }

    /**
     * This method extracts the formatted date from the InfoBox row.
     */
    protected function readInfoBoxDate(array $label): ?\DateTimeInterface
    {
        $rowText = $this->readInfoBoxText($label);

        if (null === $rowText) {
            return null;
        }

        if ($this->isLang(self::LANG_PERSIAN)) {
            $rowText = TextUtil::convertNumbersToLatin($rowText);
            // ۱ فروردین ۱۳۳۳
            $result = \preg_match("/([0-9]{1,2}) ([^a-z0-9 ]+) ([0-9]{4})/", $rowText, $matches);

            if (1 !== $result) {
                $this->logger->warning("Wikipedia Crawler - SolarHijri date not matched.", [
                    "content" => $rowText
                ]);
                return null;
            }

            return DateTimeUtil::resolveDate((int) $matches[3], $matches[2], (int) $matches[1], true);
        }

        $patterns = [
            // 1958-08-02
            // This type of date exists in some articles and is hidden, but it`s dependable.
            "default" => [
                "reg" => "/([0-9]{4})\-([0-9]{2})\-([0-9]{2})/",
                "map" => ['y' => 1, 'm' => 2, 'd' => 3]
            ],

            // American format: month-day-year (May 5, 1989)
            "american" => [
                "reg" => "/([A-Z][a-z]+) ([0-9]{1,2}), ([0-9]{4})/",
                "map" => ['y' => 3, 'm' => 1, 'd' => 2]
            ],

            // British format: day-month-year (8 September 1971)
            "british" => [
                "reg" => "/([0-9]{1,2}) ([a-z]+) ([0-9]{4})/i",
                "map" => ['y' => 3, 'm' => 2, 'd' => 1]
            ]
        ];

        foreach ($patterns as $pKey => $p) {
            $result = \preg_match($p["reg"], $rowText, $matches);
            if (1 === $result) {
                $this->logger->debug("Wikipedia Crawler - Date matched.", [
                    "date" => $matches[0],
                    "pattern" => $pKey
                ]);
                return DateTimeUtil::resolveDate(
                    (int) $matches[$p["map"]['y']],
                    $matches[$p["map"]['m']],
                    (int) $matches[$p["map"]['d']]);
            }
        }

        $this->logger->warning("Wikipedia Crawler - Date not matched.", [
            "content" => $rowText
        ]);

        return null;
    }

    protected function readInfoBoxDateRange(array $label): array
    {
        $rowText = $this->readInfoBoxText($label);
        $output = ["from" => 0, "to" => 0];

        if (null !== $rowText) {
            if ($this->isLang(self::LANG_PERSIAN)) {
                $rowText = TextUtil::convertNumbersToLatin($rowText);
            }
            $range = \explode('–', $rowText);
            if (2 !== \count($range)) {
                $range = \explode(" تا ", $rowText);
            }
            $output["from"] = isset($range[0]) ? (int) $range[0] : 0;
            $output["to"] = isset($range[1]) ? (int) $range[1] : 0;
        }

        return $output;
    }
}
