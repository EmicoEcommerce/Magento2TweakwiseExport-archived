<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2020 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

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
            $childOptions = $child->getChildOptions();
            if ($requiredOnly && $childOptions && !$childOptions->isRequired()) {
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
            $childOptions = $child->getChildOptions();
            if ($requiredOnly && $childOptions && !$childOptions->isRequired()) {
                continue;
            }
            $stockQuantities[$child->getId()] = $child->getStockQty();
        }

        return $stockQuantities;
    }
}
