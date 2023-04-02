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

trait ListCrawlerTrait
{
    /**
     * @var int
     */
    private $itemPosition = 0;

    /**
     * @var bool
     */
    private $itemRejected = false;

    protected abstract function getItemCount(): int;

    /**
     * @return bool Is any item?
     */
    public function nextItem(): bool
    {
        if ($this->isItemRejected()) {
            return false;
        }

        if (!$this->isValidItemPosition(1 + $this->itemPosition)) {
            $this->itemRejected = true;
            return false;
        }

        $this->itemPosition++;

        return true;
    }

    public function hasItem(): bool
    {
        return $this->getItemCount() > 0;
    }

    public function getItemPosition(): int
    {
        return $this->itemPosition;
    }

    public function gotoItem(int $position): bool
    {
        if (!$this->isValidItemPosition($position)) {
            return false;
        }

        $this->itemPosition = $position;

        return true;
    }

    private function isValidItemPosition(int $position): bool
    {
        return ($position < $this->getItemCount())
            && ($position >= 0);
    }

    protected function isItemRejected(): bool
    {
        return $this->itemRejected;
    }
}
