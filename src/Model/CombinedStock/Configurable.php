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

class Configurable implements CombinedStockItemInterface
{
    /**
     * @var CombinedStockHelper
     */
    protected $stockHelper;

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
        // This can happen when there are no children configured
        if (empty($childStatus) || empty($childQuantities)) {
            return $exportEntity->getStockItem();
        }

        $qty = (int) array_sum($childQuantities);
        $isInStock = min(max($childStatus), $exportEntity->getStockItem()->getIsInStock());
        $stockItem = new StockItem();
        $stockItem->setQty($qty);
        $stockItem->setIsInStock($isInStock);

        return $stockItem;
    }
}
