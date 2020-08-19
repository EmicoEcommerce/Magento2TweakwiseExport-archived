<?php

/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2020.
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use Emico\TweakwiseExport\Model\StockItem;

class ExportEntityConfigurable extends CompositeExportEntity
{

    /**
     * @var
     */
    protected $isStockCombined;

    /**
     * @return StockItem
     */
    public function getStockItem()
    {
        if ($this->isStockCombined) {
            return $this->stockItem;
        }

        if (!$this->children) {
            return $this->stockItem;
        }

        $childQty = [];
        $childStockStatus = [];

        foreach ($this->getEnabledChildren() as $child) {
            $childQty[] = $child->getStockItem()->getQty();
            $childStockStatus[] = $child->getStockItem()->getIsInStock();
        }

        if (empty($childStockStatus) || empty($childQty)) {
            $this->isStockCombined = true;
            return $this->stockItem;
        }

        $qty = (int) array_sum($childQty);
        $isInStock = min(max($childStockStatus), $this->stockItem->getIsInStock());
        $stockItem = new StockItem();
        $stockItem->setQty($qty);
        $stockItem->setIsInStock($isInStock);

        $this->stockItem = $stockItem;
        $this->isStockCombined = true;
        return $this->stockItem;
    }
}
