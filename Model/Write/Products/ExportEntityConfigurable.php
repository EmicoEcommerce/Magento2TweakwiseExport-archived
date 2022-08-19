<?php

namespace Tweakwise\Magento2TweakwiseExport\Model\Write\Products;

use Tweakwise\Magento2TweakwiseExport\Traits\Stock\HasStockThroughChildren;

/**
 * Class ExportEntityConfigurable
 * @package Tweakwise\Magento2TweakwiseExport\Model\Write\Products
 */
class ExportEntityConfigurable extends CompositeExportEntity
{
    use HasStockThroughChildren;

    /**
     * @var bool
     */
    protected $isStockCombined;
}
