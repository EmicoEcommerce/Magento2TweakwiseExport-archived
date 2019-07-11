<?php
/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2019.
 */

namespace Emico\TweakwiseExport\Model;


class StockItem
{
    /**
     * @var int
     */
    private $qty = 0;

    /**
     * @var int
     */
    private $isInStock = 0;

    /**
     * @return int
     */
    public function getQty(): int
    {
        return $this->qty;
    }

    /**
     * @param int
     */
    public function setQty(int $qty): void
    {
        $this->qty = $qty;
    }

    /**
     * @return int
     */
    public function getIsInStock(): int
    {
        return $this->isInStock;
    }

    /**
     * @param int $isInStock
     */
    public function setIsInStock(int $isInStock): void
    {
        $this->isInStock = $isInStock;
    }
}