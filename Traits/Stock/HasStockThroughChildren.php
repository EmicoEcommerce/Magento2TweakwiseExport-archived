<?php

namespace Tweakwise\Magento2TweakwiseExport\Traits\Stock;

use Tweakwise\Magento2TweakwiseExport\Model\StockItem;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;

/**
 * Trait HasChildren
 * @package Tweakwise\Magento2TweakwiseExport\Traits
 */
trait HasStockThroughChildren
{
    /**
     * @return StockItem
     */
    public function getStockItem(): ?StockItem
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
        $isInStock = min(
            max($childStockStatus),
            $this->getTypeId() === Configurable::TYPE_CODE ? 1 : $this->stockItem->getIsInStock()
        );
        $stockItem = new StockItem();
        $stockItem->setQty($qty);
        $stockItem->setIsInStock($isInStock);

        $this->stockItem = $stockItem;
        $this->isStockCombined = true;
        return $this->stockItem;
    }
}
