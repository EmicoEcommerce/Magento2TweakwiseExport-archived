<?php

/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2020.
 */

namespace Emico\TweakwiseExport\Model\CombinedStock;

use Emico\TweakwiseExport\Model\StockItem;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;

class Bundle implements CombinedStockItemInterface
{
    /**
     * @param ExportEntity $exportEntity
     * @return StockItem
     */
    public function getCombinedStockItem(ExportEntity $exportEntity): StockItem
    {
        $optionGroups = [];
        foreach ($exportEntity->getExportChildren() as $child) {
            $childOptions = $child->getChildOptions();
            if (!$childOptions) {
                continue;
            }
            $optionId = $childOptions->getOptionId();
            $optionGroups[$optionId]['is_in_stock'] =
                isset($optionGroups[$optionId]['is_in_stock'])
                    ? max($optionGroups[$optionId]['is_in_stock'], $child->getStockItem()->getIsInStock())
                    : $child->getStockItem()->getIsInStock();
            $optionGroups[$optionId]['qty'] =
                isset($optionGroups[$optionId]['qty'])
                    ? $optionGroups[$optionId]['qty'] + $child->getStockItem()->getQty()
                    : $child->getStockItem()->getQty();

        }

        if (empty($optionGroups)) {
            return $exportEntity->getStockItem();
        }

        $qty = min(...array_column($optionGroups, 'qty'));
        $isInStock = min(...array_column($optionGroups, 'is_in_stock'));
        $stockItem = new StockItem();
        $stockItem->setQty($qty);
        $stockItem->setIsInStock($isInStock);

        return $stockItem;
    }
}
