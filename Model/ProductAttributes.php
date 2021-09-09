<?php
/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2019.
 */

namespace Emico\TweakwiseExport\Model;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class ProductAttributes
 * @package Emico\TweakwiseExport\Model
 */
class ProductAttributes
{
    /**
     * @var EavConfig
     */
    protected EavConfig $eavConfig;

    /**
     * Apparently some of magento core attributes are marked as static
     * but their values are not saved in table catalog_product_entity
     * we cannot export these attributes.
     *
     * @var array
     */
    protected array $attributeBlacklist;

    /**
     * ProductAttributes constructor.
     * @param EavConfig $eavConfig
     * @param array $attributeBlacklist
     */
    public function __construct(
        EavConfig $eavConfig,
        array $attributeBlacklist
    ) {
        $this->eavConfig = $eavConfig;
        $this->attributeBlacklist = $attributeBlacklist;
    }

    /**
     * @param string[]|null $attributeCodes
     * @return Attribute[]
     */
    public function getAttributesToExport(array $attributeCodes = null): array
    {
        try {
            $type = $this->eavConfig->getEntityType(Product::ENTITY);
        } catch (LocalizedException $e) {
            return [];
        }

        $attributeCollection = $type->getAttributeCollection();
        if (!empty($attributeCodes)) {
            $attributeCollection->addFieldToFilter(
                'attribute_code',
                ['in' => $attributeCodes]
            );
        }

        $attributesForExport = [];
        foreach ($attributeCollection as $attribute) {
            if (!$this->shouldExportAttribute($attribute)) {
                continue;
            }

            $attributesForExport[] = $attribute;
        }

        return $attributesForExport;
    }

    /**
     * @param Attribute $attribute
     * @return bool
     */
    protected function shouldExportAttribute(Attribute $attribute): bool
    {
        $isBlackListed = $this->isAttributeBlacklisted($attribute);
        return !$isBlackListed &&
            (
                $attribute->getUsedInProductListing() ||
                $attribute->getIsFilterable() ||
                $attribute->getIsFilterableInSearch() ||
                $attribute->getIsSearchable() ||
                $attribute->getIsVisibleInAdvancedSearch() ||
                $attribute->getUsedForSortBy()
            );
    }

    /**
     * @param Attribute $attribute
     * @return bool
     */
    protected function isAttributeBlacklisted(Attribute $attribute): bool
    {
        return \in_array(
            $attribute->getAttributeCode(),
            $this->attributeBlacklist,
            true
        );
    }
}
