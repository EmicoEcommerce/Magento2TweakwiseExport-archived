<?php

/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2020.
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use Emico\TweakwiseExport\Traits\Stock\HasStockThroughChildren;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

/**
 * Class ExportEntityBundle
 * @package Emico\TweakwiseExport\Model\Write\Products
 */
class ExportEntityBundle extends CompositeExportEntity
{
    use HasStockThroughChildren;

    /**
     * @var bool
     */
    protected $isStockCombined;
}
