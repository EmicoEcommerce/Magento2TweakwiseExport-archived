<?php

/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2020.
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use Emico\TweakwiseExport\Model\StockItem;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

/**
 * Class ExportEntityBundle
 * @package Emico\TweakwiseExport\Model\Write\Products
 */
class ExportEntityBundle extends CompositeExportEntity
{
    /**
     * @var bool
     */
    protected bool $isStockCombined;

    /**
     * @return StockItem
     */
    public function getStockItem(): ?StockItem
    {
        if ($this->isStockCombined) {
            return $this->stockItem;
        }

        if (!$this->children) {
            return $this->stockItem;
        }

        $optionGroups = [];
        foreach ($this->getEnabledChildren() as $child) {
            $childOptions = $child->getChildOptions();
            if (!$childOptions) {
                continue;
            }

            $optionId = $childOptions->getOptionId();
            $childQty = $child->getStockItem() ? $child->getStockItem()->getQty() : 0;

            $optionGroups[$optionId]['qty'] =
                isset($optionGroups[$optionId]['qty'])
                    ? $optionGroups[$optionId]['qty'] + $childQty
                    : $childQty;

            if (isset($optionGroups[$optionId]['is_in_stock']) && $optionGroups[$optionId]['is_in_stock']) {
                continue;
            }

            if (!$childOptions->isRequired()) {
                $optionGroups[$optionId]['is_in_stock'] = 1;
            }

            $optionGroups[$optionId]['is_in_stock'] = $child->getStockItem()->getIsInStock();
        }

        if (empty($optionGroups)) {
            $this->isStockCombined = true;
            return $this->stockItem;
        }

        $qty = min(array_column($optionGroups, 'qty'));
        $isInStock = min($this->stockItem->getIsInStock(), ...array_column($optionGroups, 'is_in_stock'));
        $stockItem = new StockItem();
        $stockItem->setQty($qty);
        $stockItem->setIsInStock($isInStock);

        $this->stockItem = $stockItem;
        $this->isStockCombined = true;
        return $this->stockItem;
    }

    /**
     * @return bool
     */
    protected function shouldExportByChildStatus(): bool
    {
        $optionGroupStatus = [];
        foreach ($this->getAllChildren() as $child) {
            $childOptions = $child->getChildOptions();
            if (!$childOptions) {
                continue;
            }
            $optionId = $childOptions->getOptionId();
            if (!$childOptions->isRequired()) {
                $optionGroupStatus[$optionId] = 1;
                continue;
            }
            if (isset($optionGroupStatus[$optionId]) && $optionGroupStatus[$optionId]) {
                continue;
            }
            $childStatus = $child->getStatus() === Status::STATUS_ENABLED ? 1 : 0;
            $optionGroupStatus[$optionId] = $childStatus;
        }

        return (empty($optionGroupStatus)) || array_product($optionGroupStatus) === 1;
    }
}
