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

class PriceField implements ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'rule_price,final_price,price,min_price', 'label' => 'Rule Price -> Final Price -> Price -> Min price'],
            ['value' => 'final_price,rule_price,price,min_price', 'label' => 'Final Price -> Rule Price -> Price -> Min price'],
            ['value' => 'final_price,rule_price,price,max_price', 'label' => 'Final Price -> Rule Price -> Price -> Max price'],
            ['value' => 'final_price,price,rule_price,min_price', 'label' => 'Final Price -> Price -> Rule Price -> Min price'],
            ['value' => 'final_price,price,rule_price,max_price', 'label' => 'Final Price -> Price -> Rule Price -> Max price'],
            ['value' => 'price,rule_price,final_price,min_price', 'label' => 'Price -> Rule Price -> Final Price -> Min price'],
            ['value' => 'price,rule_price,final_price,max_price', 'label' => 'Price -> Rule Price -> Final Price -> Max price'],
            ['value' => 'price,final_price,rule_price,min_price', 'label' => 'Price -> Final Price -> Rule Price -> Min price'],
            ['value' => 'price,final_price,rule_price,max_price', 'label' => 'Price -> Final Price -> Rule Price -> Max price'],
            ['value' => 'final_price,price,min_price', 'label' => 'Final Price -> Price -> Min price'],
            ['value' => 'final_price,price,max_price', 'label' => 'Final Price -> Price -> Max price'],
            ['value' => 'price,final_price,min_price', 'label' => 'Price -> Final Price -> Min price'],
            ['value' => 'price,final_price,max_price', 'label' => 'Price -> Final Price -> Max price'],
            ['value' => 'final_price,min_price', 'label' => 'Final Price -> Min price'],
            ['value' => 'final_price,max_price', 'label' => 'Final Price -> Max price'],
            ['value' => 'price,min_price', 'label' => 'Price -> Min price'],
            ['value' => 'price,max_price', 'label' => 'Price -> Max price'],
            ['value' => 'rule_price', 'label' => 'Rule Price'],
            ['value' => 'final_price', 'label' => 'Final Price'],
            ['value' => 'price', 'label' => 'Price'],
            ['value' => 'min_price', 'label' => 'Min price'],
            ['value' => 'max_price', 'label' => 'Max price'],
        ];
    }
}
