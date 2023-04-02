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

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DomCrawler\Crawler;

class TableCrawler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    const TAG = "[Chakavang Table Crawler] ";

    private $columnsNumber;
    private $columnMap = [];
    private $rows;

    public function __construct(Crawler $table, array $dictionary)
    {
        // Find all of the rows.
        $this->rows = $table->filter("tr");
        // Find all of the header cells.
        $headerCells = $this->rows->filter("th");

        if (0 !== \count($headerCells)) {
            // Find the cells of the last header row.
            // In some tables, the first header row contains only title of the table.
            $headerCells = $headerCells
                ->last()
                ->parents()
                ->first()
                ->filter("th");
        } else {
            $this->logger->warning(self::TAG . "Table has not header cells.");
            for ($i = 0; $i < \count($this->rows); $i++) {
                $row = $this->getRow($i);
                if (1 < $row->countCells()) {
                    $headerCells = $row->getNode();
                    unset($this->rows[$i]);
                }
            }
        }

        $this->columnsNumber = \count($headerCells);
        $this->generateColumnMap($dictionary, $headerCells);
    }

    /**
     * Redefines title of the each column by dictionary, and returns new titles as array.
     */
    private function generateColumnMap(array $dictionary, Crawler $headerCells)
    {
        $headerCells->each(function (Crawler $cell, $position) use (&$dictionary) {
            foreach ($dictionary as $dicWord => $dicItems) {
                $cellText = \mb_strtolower(\trim($cell->text()));
                if (\in_array($cellText, $dicItems, true)) {
                    $this->columnMap[$dicWord] = $position;
                    unset($dictionary[$dicWord]);
                    break;
                }
            }
        });
    }

    protected function getRow(int $position): TableRowCrawler
    {
        return new TableRowCrawler($this->rows->eq($position), $this->columnMap);
    }

    public function eachRow(\Closure $closure): void
    {
        for ($i = 0; $i < \count($this->rows); $i++) {
            $row = $this->getRow($i);
            if ($row->countCells() === $this->columnsNumber) {
                $closure($row);
            }
        }
    }
}
