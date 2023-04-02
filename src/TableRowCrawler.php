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

use Chakavang\Crawlman\Util\TextUtil;
use Symfony\Component\DomCrawler\Crawler;

class TableRowCrawler
{
    private $node;
    private $cells;
    private $columnMap;

    public function __construct(Crawler $row, array &$columnMap)
    {
        $this->node = $row;
        $this->cells = $row->filter("td");
        $this->columnMap = $columnMap;
    }

    public function countCells(): int
    {
        return \count($this->cells);
    }

    public function readCell(string $column): ?Crawler
    {
        return !isset($this->columnMap[$column]) ? null
            : $this->cells->eq((int) $this->columnMap[$column]);
    }

    public function readCellText(string $column): ?string
    {
        $cell = $this->readCell($column);

        if (null === $cell) {
            return null;
        }

        $value = \trim(TextUtil::normalizedHtmlSpaces($cell->text()));

        return empty($value) ? null : $value;
    }

    public function readMultiItemCell(string $column, \Closure $closure): void
    {
        $cell = $this->readCell($column);

        if (null === $cell) {
            return;
        }

        $itemNodes = $cell->filter("li");

        if (0 < \count($itemNodes)) {
            $itemNodes->each(function (Crawler $itemNode) use ($closure) {
                $text = \trim(TextUtil::normalizedHtmlSpaces($itemNode->text()));
                $link = $itemNode->filter("a")->first();
                $closure($text, (0 === \count($link)) ? null : $link->attr("href"));
            });

            return;
        }

        $valueList = TextUtil::explode($cell->text());
        $linkNodes = $cell->filter("a");
        $linksValues = [];

        $linkNodes->each(function (Crawler $node) use (&$linksValues) {
            $url = $node->attr("href");
            $linksValues[$url] = $node->text();
        });

        foreach ($valueList as $value) {
            $key = \array_search($value, $linksValues);
            $closure($value, (false === $key) ? null : $key);
        }
    }

    public function getNode(): Crawler
    {
        return $this->node;
    }
}
