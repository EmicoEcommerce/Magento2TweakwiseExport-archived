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

interface CombinedStockItemInterface
{
    /**
     * This method should create a stock item that represents the current magento stock state for that item
     * this process should take the product type into account, examples:
     * A configurable products stock qty should be the sum of all the in stock children
     * A bundle should have the minimum of all the quantities of the child product
     * It does so based on the StockItems set on the entity itself and the stock items on its children
     *
     * @param ExportEntity $exportEntity
     * @return StockItem
     */
    public function getCombinedStockItem(ExportEntity $exportEntity): StockItem;
}
