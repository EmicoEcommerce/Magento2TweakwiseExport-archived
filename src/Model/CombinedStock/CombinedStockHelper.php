<?php

/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2020.
 */
declare(strict_types=1);

namespace Emico\TweakwiseExport\Model\CombinedStock;

use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;

class CombinedStockHelper
{
    /**
     * @param ExportEntity $exportEntity
     * @param bool $requiredOnly
     * @return int[]
     */
    public function getChildStockStatus(ExportEntity $exportEntity, $requiredOnly = false)
    {
        $stockStatus = [];
        foreach ($exportEntity->getExportChildren() as $child) {
            if ($requiredOnly && !$child->isRequired()) {
                continue;
            }
            $stockStatus[$child->getId()] = $child->getStockItem()->getIsInStock();
        }

        return $stockStatus;
    }

    /**
     * @param ExportEntity $exportEntity
     * @param bool $requiredOnly
     * @return int[]
     */
    public function getChildStockQuantities(ExportEntity $exportEntity, $requiredOnly = false)
    {
        $stockQuantities = [];
        foreach ($exportEntity->getExportChildren() as $child) {
            if ($requiredOnly && !$child->isRequired()) {
                continue;
            }
            $stockQuantities[$child->getId()] = $child->getStockQty();
        }

        return $stockQuantities;
    }
}
