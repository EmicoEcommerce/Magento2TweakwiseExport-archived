<?php

/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2020.
 */

namespace Emico\TweakwiseExport\Model\CombinedStock;

use Emico\TweakwiseExport\Model\StockItem;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;

class Configurable implements CombinedStockItemInterface
{
    /**
     * @var CombinedStockHelper
     */
    private $stockHelper;

    /**
     * Configurable constructor.
     * @param CombinedStockHelper $stockHelper
     */
    public function __construct(CombinedStockHelper $stockHelper)
    {
        $this->stockHelper = $stockHelper;
    }

    /**
     * @param ExportEntity $exportEntity
     * @return StockItem
     */
    public function getCombinedStockItem(ExportEntity $exportEntity): StockItem
    {
        $childQuantities = $this->stockHelper->getChildStockQuantities($exportEntity);
        $childStatus = $this->stockHelper->getChildStockStatus($exportEntity);

        $qty = array_sum($childQuantities);
        $isInStock = min(max($childStatus), $exportEntity->getStockItem()->getIsInStock());
        $stockItem = new StockItem();
        $stockItem->setQty($qty);
        $stockItem->setIsInStock($isInStock);

        return $stockItem;
    }
}
