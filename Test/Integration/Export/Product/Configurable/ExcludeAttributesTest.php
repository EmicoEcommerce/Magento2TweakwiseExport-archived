<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Tweakwise\Magento2TweakwiseExport\Test\Integration\Export\Product\Configurable;

use Tweakwise\Magento2TweakwiseExport\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Test\Integration\ExportTest;
use Tweakwise\Magento2TweakwiseExport\TestHelper\Data\Product\AttributeProvider;
use Tweakwise\Magento2TweakwiseExport\TestHelper\Data\Product\ConfigurableProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

/**
 * @magentoDbIsolation enabled
 * @@magentoAppIsolation enabled
 */
class ExcludeAttributesTest extends ExportTest
{
    /**
     * @var AttributeProvider
     */
    protected $attributeProvider;

    /**
     * @var ConfigurableProvider
     */
    protected $configurableProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp() : void
    {
        parent::setUp();
        $this->configurableProvider = $this->getObject(ConfigurableProvider::class);
        $this->attributeProvider = $this->getObject(AttributeProvider::class);
    }

    /**
     * Test to see if attributes of simple products are not shown
     */
    public function testAttributesWhenDisabled()
    {
        /** @var Product $product */
        $product = $this->configurableProvider->create([
            ['color' => 'Black', 'status' => Status::STATUS_ENABLED],
            ['color' => 'Blue', 'status' => Status::STATUS_ENABLED],
            ['color' => 'White', 'status' => Status::STATUS_DISABLED],
        ]);

        $feed = $this->exportFeed();
        $feed->getProduct($product->getId())->assertAttributes(['color' => ['Black', 'Blue']]);

        /** @var ProductInterface[] $children */
        $children = $product->getData(ConfigurableProvider::GENERATED_CHILD_PRODUCTS);
        $this->assertCount(3, $children);
        foreach ($children as $child) {
            $feed->assertProductMissing($child->getId());
        }
    }

    /**
     * Test to see if child attributes are taken     from the parent but not from the child when they are set to disabled
     * in configuration.
     */
    public function testAttributesHiddenWhenExcluded()
    {
        $this->setConfig(Config::PATH_EXCLUDE_CHILD_ATTRIBUTES, 'color');

        $parentColorId = $this->attributeProvider->getOptionId('color', 'parent color');
        $childColorId = $this->attributeProvider->getOptionId('color', 'child color');

        $product = $this->configurableProvider->create(
            [['color' => $childColorId]],
            ['color' => $parentColorId]
        );

        $this->exportFeed()->getProduct($product->getId())->assertAttributes(['color' => 'parent color']);
    }

    /**
     * Test to see if child attributes are taken from the parent but not from the child when they are set to disabled
     * in configuration.
     */
    public function testAttributesHiddenWhenIncluded()
    {
        $parentColorId = $this->attributeProvider->getOptionId('color', 'parent color');
        $childColorId = $this->attributeProvider->getOptionId('color', 'child color');

        $product = $this->configurableProvider->create(
            [['color' => $childColorId]],
            ['color' => $parentColorId]
        );

        $this->exportFeed()->getProduct($product->getId())->assertAttributes(['color' => ['parent color', 'child color']]);
    }
}
