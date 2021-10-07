<?php

/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2020.
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use Emico\TweakwiseExport\Traits\Stock\HasStockThroughChildren;

/**
 * Class ExportEntityConfigurable
 * @package Emico\TweakwiseExport\Model\Write\Products
 */
class ExportEntityConfigurable extends CompositeExportEntity
{
    use HasStockThroughChildren;

    /**
     * @var bool
     */
    protected bool $isStockCombined;
}
