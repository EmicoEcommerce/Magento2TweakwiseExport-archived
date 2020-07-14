<?php

/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2020 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
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

        $exportEntityStockItem = $exportEntity->getStockItem();
        if (empty($optionGroups)) {
            return $exportEntityStockItem;
        }

        $qty = min(array_column($optionGroups, 'qty'));
        $isInStock = min($exportEntityStockItem->getIsInStock(), ...array_column($optionGroups, 'is_in_stock'));
        $stockItem = new StockItem();
        $stockItem->setQty($qty);
        $stockItem->setIsInStock($isInStock);

        return $stockItem;
    }
}
