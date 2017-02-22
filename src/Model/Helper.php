<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Model;

use Magento\Catalog\Model\ResourceModel\Eav\Attribute;

class Helper
{
    /**
     * @param int $storeId
     * @param int $entityId
     * @return string
     */
    public function getTweakwiseId($storeId, $entityId)
    {
        // Prefix 1 is to make sure it stays the same length when casting to int
        return '1' . str_pad($storeId, 4, '0', STR_PAD_LEFT) . $entityId;
    }

    /**
     * @param int $id
     *
     * @return int
     */
    public function getStoreId($id)
    {
        return substr($id, 5);
    }

    /**
     * @param Attribute $attribute
     * @return bool
     */
    public function shouldExportAttribute(Attribute $attribute)
    {
        if (
            !$attribute->getUsedInProductListing() &&
            !$attribute->getIsFilterable() &&
            !$attribute->getIsFilterableInSearch() &&
            !$attribute->getIsFilterableInGrid() &&
            !$attribute->getIsSearchable() &&
            !$attribute->getIsVisibleInAdvancedSearch() &&
            !$attribute->getUsedForSortBy()
        ) {
            return false;
        }

        return true;
    }
}
