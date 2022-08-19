<?php

namespace Tweakwise\Magento2TweakwiseExport\Model\Write\Products\CollectionDecorator\StockData;

use Tweakwise\Magento2TweakwiseExport\Model\StockItem;
use Tweakwise\Magento2TweakwiseExport\Model\Write\Products\Collection;
use Magento\Store\Model\Store;

/**
 * Interface StockMapProviderInterface
 * @package Tweakwise\Magento2TweakwiseExport\Model\Write\Products\CollectionDecorator\StockData
 */
interface StockMapProviderInterface
{
    /**
     * Should return an array keyed by product entity_id
     * the values should be StockItem objects
     *
     * @param Collection $collection
     * @return StockItem[]
     */
    public function getStockItemMap(Collection $collection);
}
