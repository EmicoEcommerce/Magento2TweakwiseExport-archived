<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2TweakwiseExport\Model\Config\Source;

use Tweakwise\Magento2TweakwiseExport\Model\ProductAttributes as ProductAttributesHelper;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class ProductAttributes
 * @package Tweakwise\Magento2TweakwiseExport\Model\Config\Source
 */
class ProductAttributes implements OptionSourceInterface
{
    /**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @var ProductAttributesHelper
     */
    protected $productAttributesHelper;

    /**
     * ProductAttributes constructor.
     *
     * @param EavConfig $eavConfig
     * @param ProductAttributesHelper $productAttributesHelper
     */
    public function __construct(
        EavConfig $eavConfig,
        ProductAttributesHelper $productAttributesHelper
    ) {
        $this->eavConfig = $eavConfig;
        $this->productAttributesHelper = $productAttributesHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $result = [];
        foreach ($this->productAttributesHelper->getAttributesToExport() as $attribute) {

            $attributeCode = $attribute->getAttributeCode();
            $result[] = [
                'value' => $attributeCode,
                'label' => sprintf(
                    '%s [%s]',
                    $attribute->getDefaultFrontendLabel(),
                    $attributeCode
                )
            ];
        }

        usort(
            $result,
            function (array $a, array $b) {
                return strnatcasecmp($a['label'], $b['label']);
            }
        );

        return $result;
    }
}
