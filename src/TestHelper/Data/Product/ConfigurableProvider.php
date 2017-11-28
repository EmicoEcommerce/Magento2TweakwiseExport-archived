<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\TestHelper\Data\Product;

use Emico\TweakwiseExport\TestHelper\Data\ProductProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductRepository;
use Magento\ConfigurableProduct\Helper\Product\Options\Factory as OptionsFactory;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use RuntimeException;
use Zend\Json\Json;

class ConfigurableProvider
{
    /**
     * Generated child product names
     */
    const GENERATED_CHILD_PRODUCTS = '_generated_child_products';

    /**
     * @var ProductProvider
     */
    private $productProvider;

    /**
     * @var AttributeProvider
     */
    private $attributeProvider;

    /**
     * @var OptionsFactory
     */
    private $optionsFactory;
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * ConfigurableProvider constructor.
     *
     * @param ProductProvider $productProvider
     * @param AttributeProvider $attributeProvider
     * @param OptionsFactory $optionsFactory
     * @param ProductRepository $productRepository
     */
    public function __construct(
        ProductProvider $productProvider,
        AttributeProvider $attributeProvider,
        OptionsFactory $optionsFactory,
        ProductRepository $productRepository
    )
    {
        $this->productProvider = $productProvider;
        $this->attributeProvider = $attributeProvider;
        $this->optionsFactory = $optionsFactory;
        $this->productRepository = $productRepository;
    }

    /**
     * @param array $simpleData
     * @param array $productData
     * @param array $configurableAttributes
     * @return ProductInterface
     */
    public function create(
        array $simpleData,
        array $productData = [],
        array $configurableAttributes = ['color']
    ): ProductInterface
    {
        if (!isset($productData['type_id'])) {
            $productData['type_id'] = Configurable::TYPE_CODE;
        }

        if (!isset($productData['qty'])) {
            $productData['qty'] = 0;
        }

        $product = $this->productProvider->create($productData);
        foreach ($configurableAttributes as $attribute) {
            $this->attributeProvider->ensureSet($attribute, $product->getAttributeSetId());
        }

        $simpleProducts = $this->createSimpleProducts($simpleData, $configurableAttributes);
        $configurableOptions = $this->createConfigurableOptions($configurableAttributes);

        $extensionAttributes = $product->getExtensionAttributes();
        if ($extensionAttributes === null) {
            return $product;
        }

        $extensionAttributes->setConfigurableProductOptions($configurableOptions);
        $extensionAttributes->setConfigurableProductLinks(array_keys($simpleProducts));
        $product->setExtensionAttributes($extensionAttributes);

        $product = $this->productRepository->save($product);
        $product->addData([self::GENERATED_CHILD_PRODUCTS => $simpleProducts]);
        return $product;
    }

    /**
     * @param array $simplesData
     * @param array $configurableAttributes
     * @return array
     */
    private function createSimpleProducts(array $simplesData, array $configurableAttributes): array
    {
        $result = [];
        foreach ($simplesData as $data) {
            $product = $this->createSimpleProduct($data, $configurableAttributes);

            $result[$product->getId()] = $product;
        }

        return $result;
    }

    /**
     * @param array $data
     * @param array $configurableAttributes
     * @return ProductInterface
     */
    private function createSimpleProduct(array $data, array $configurableAttributes): ProductInterface
    {
        // Rewrite configurable data to option values
        foreach ($configurableAttributes as $attributeCode) {
            if (!isset($data[$attributeCode])) {
                throw new RuntimeException(sprintf(
                    'Attribute code %s does not exists in simple data %s',
                    $attributeCode,
                    Json::encode($data)
                ));
            }

            if (!\is_int($data[$attributeCode])) {
                $data[$attributeCode] = $this->attributeProvider->getOptionId($attributeCode, $data[$attributeCode]);
            }
        }

        if (!isset($data['visibility'])) {
            $data['visibility'] = Product\Visibility::VISIBILITY_NOT_VISIBLE;
        }

        return $this->productProvider->create($data);
    }

    /**
     * @param array $configurableAttributes
     * @return array
     */
    private function createConfigurableOptions(array $configurableAttributes): array
    {
        // Create configurable attributes data
        $configurableAttributesData = [];
        foreach ($configurableAttributes as $attributeCode) {
            $attribute = $this->attributeProvider->get($attributeCode);

            $options = $attribute->getOptions();
            array_shift($options); //remove the first option which is empty

            $attributeValues = [];
            foreach ($options as $option) {
                $attributeValues[] = [
                    'label' => $option->getLabel(),
                    'attribute_id' => $attribute->getAttributeId(),
                    'value_index' => $option->getValue(),
                ];
            }

            $configurableAttributesData[] = [
                'attribute_id' => $attribute->getAttributeId(),
                'code' => $attribute->getAttributeCode(),
                'label' => $attribute->getDefaultFrontendLabel(),
                'position' => '0',
                'values' => $attributeValues,
            ];
        }

        return $this->optionsFactory->create($configurableAttributesData);
    }
}