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

class Simple implements CombinedStockItemInterface
{
    /**
     * @param ExportEntity $exportEntity
     * @return StockItem
     */
    public function getCombinedStockItem(ExportEntity $exportEntity): StockItem
    {
        return $exportEntity->getStockItem();
    }
}
