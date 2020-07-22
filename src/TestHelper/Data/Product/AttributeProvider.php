<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\TestHelper\Data\Product;

use Magento\Catalog\Model\Product;
use Magento\Catalog\Setup\CategorySetup;
use Magento\Eav\Api\Data\AttributeInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\Option;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Option\CollectionFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;

class AttributeProvider
{
    /**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @var CategorySetup
     */
    protected $categorySetup;

    /**
     * @var EavSetup
     */
    protected $eavSetup;

    /**
     * @var CollectionFactory
     */
    protected $attributeOptionCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * AttributeProvider constructor.
     *
     * @param EavConfig $eavConfig
     * @param CategorySetup $categorySetup
     * @param EavSetup $eavSetup
     * @param CollectionFactory $attributeOptionCollectionFactory
     */
    public function __construct(
        EavConfig $eavConfig,
        CategorySetup $categorySetup,
        EavSetup $eavSetup,
        CollectionFactory $attributeOptionCollectionFactory,
        StoreManagerInterface $storeManager
    )
    {
        $this->eavConfig = $eavConfig;
        $this->categorySetup = $categorySetup;
        $this->eavSetup = $eavSetup;
        $this->attributeOptionCollectionFactory = $attributeOptionCollectionFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * Fetches product attribute
     *
     * @param string $code
     * @return AttributeInterface
     * @throws LocalizedException
     */
    public function get(string $code): AttributeInterface
    {
        return $this->eavConfig->getAttribute(Product::ENTITY, $code);
    }

    /**
     * @param string $set
     * @return int
     * @throws LocalizedException
     */
    public function getSetId(string $set = 'Default'): int
    {
        return (int) $this->categorySetup->getAttributeSetId(Product::ENTITY, $set);
    }

    /**
     * @param string $code
     * @param string|int $set
     * @return $this
     */
    public function ensureSet(string $code, $set): self
    {
        $this->categorySetup->addAttributeToGroup(Product::ENTITY, $set, 'Default', $code);
        return $this;
    }

    /**
     * Fetches or creates option id for product attribute
     *
     * @param string $code
     * @param string $label
     * @return int
     * @throws LocalizedException
     */
    public function getOptionId(string $code, string $label): int
    {
        $attribute = $this->get($code);
        $collection = $this->attributeOptionCollectionFactory->create();
        $collection->setAttributeFilter($attribute->getAttributeId());
        $collection->addFilter('tdv.value', $label);
        $collection->setStoreFilter($this->storeManager->getStore()->getId());

        /** @var Option $option */
        $option = $collection->getFirstItem();
        if ($option->getId()) {
            return $option->getId();
        }

        $this->eavSetup->addAttributeOption([
            'values' => [0 => $label],
            'attribute_id' => $attribute->getAttributeId(),
        ]);

        $attribute->setOptions();
        return $this->getOptionId($code, $label);
    }

    /**
     * @param string $code
     * @param string $label
     * @throws LocalizedException
     */
    public function deleteOption(string $code, string $label)
    {
        $attribute = $this->get($code);
        $optionId = $this->getOptionId($code, $label);
        $this->eavSetup->addAttributeOption([
            'value' => [$optionId => ''],
            'delete' => [$optionId => true],
            'attribute_id' => $attribute->getAttributeId(),
        ]);
        $attribute->setOptions();
    }
}
