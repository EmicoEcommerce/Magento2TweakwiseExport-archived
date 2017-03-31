<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Model\Config\Source;

use Emico\TweakwiseExport\Model\Helper;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Option\ArrayInterface;

class ProductAttributes implements ArrayInterface
{
    /**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * ProductAttributes constructor.
     *
     * @param EavConfig $eavConfig
     * @param Helper $helper
     */
    public function __construct(EavConfig $eavConfig, Helper $helper)
    {
        $this->eavConfig = $eavConfig;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        $config = $this->eavConfig;
        $result = [];
        foreach ($config->getEntityAttributeCodes(Product::ENTITY) as $attributeCode) {
            /** @var Attribute $attribute */
            $attribute = $config->getAttribute(Product::ENTITY, $attributeCode);
            if (!$attribute->getData('is_visible')) {
                continue;
            }

            if (!$this->helper->shouldExportAttribute($attribute)) {
                continue;
            }

            $result[] = [
                'value' => $attributeCode,
                'label' => sprintf('%s [%s]', $attribute->getDefaultFrontendLabel(), $attributeCode),
            ];
        }

        usort($result, function(array $a, array $b) {
            return strnatcasecmp($a['label'], $b['label']);
        });

        return $result;
    }
}
