<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Test\Integration\Export\Product\Configurable;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Test\Integration\ExportTest;
use Emico\TweakwiseExport\TestHelper\Data\Product\AttributeProvider;
use Emico\TweakwiseExport\TestHelper\Data\Product\ConfigurableProvider;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

/**
 * @IgnoreAnnotation("magentoDbIsolation")
 * @magentoDbIsolation enabled
 * @@magentoAppIsolation enabled
 */
class ChildExportedTest extends ExportTest
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
     * Test to see if export where simples are also visible work as expected.
     */
    public function testVisibleSimples()
    {
        /** @var Product $product */
        $product = $this->configurableProvider->create([
            ['color' => 'black', 'status' => Status::STATUS_ENABLED, 'visibility' => Product\Visibility::VISIBILITY_BOTH],
            ['color' => 'blue', 'status' => Status::STATUS_ENABLED, 'visibility' => Product\Visibility::VISIBILITY_BOTH],
        ]);

        $feed = $this->exportFeed();
        $feed->getProduct($product->getId());

        /** @var ProductInterface[] $children */
        $children = $product->getData(ConfigurableProvider::GENERATED_CHILD_PRODUCTS);
        $this->assertCount(2, $children);
        foreach ($children as $child) {
            $feed->getProduct($child->getId());
        }
    }
}
