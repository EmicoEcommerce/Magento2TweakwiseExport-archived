<?php

namespace Tweakwise\Magento2TweakwiseExport\Model\Write\Products;

use Tweakwise\Magento2TweakwiseExport\Traits\Stock\HasStockThroughChildren;

/**
 * Class ExportEntityGrouped
 * @package Tweakwise\Magento2TweakwiseExport\Model\Write\Products
 */
class ExportEntityGrouped extends CompositeExportEntity
{
    use HasStockThroughChildren;

    /**
     * @var bool
     */
    protected $isStockCombined;
}
