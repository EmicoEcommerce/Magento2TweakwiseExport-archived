<?php
/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2019.
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator\StockData;

use Emico\TweakwiseExport\Model\StockItem;
use Emico\TweakwiseExport\Model\Write\Products\Collection;

/**
 * Interface StockMapProviderInterface
 * @package Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator\StockData
 */
interface StockMapProviderInterface
{
    /**
     * Should return an array keyed by product entity_id
     * the values should be StockItem objects
     *
     * @param Collection $collection
     * @param int $storeId
     * @return StockItem[]
     */
    public function getStockItemMap(Collection $collection, int $storeId);
}
