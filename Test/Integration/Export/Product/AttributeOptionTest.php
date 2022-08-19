<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Tweakwise\Magento2TweakwiseExport\Test\Integration\Export\Product;

use Tweakwise\Magento2TweakwiseExport\Test\Integration\ExportTest;
use Tweakwise\Magento2TweakwiseExport\TestHelper\Data\Product\AttributeProvider;
use Tweakwise\Magento2TweakwiseExport\TestHelper\Data\Product\ConfigurableProvider;

class AttributeOptionTest extends ExportTest
{
    /**
     * @var ConfigurableProvider
     */
    protected $configurableProvider;

    /**
     * @var AttributeProvider
     */
    protected $attributeProvider;

    /**
     * Make sure export is enabled and set some much used objects
     */
    protected function setUp() : void
    {
        parent::setUp();

        $this->configurableProvider = $this->getObject(ConfigurableProvider::class);
        $this->attributeProvider = $this->getObject(AttributeProvider::class);
    }

    /**
     * Test exporting a product with deleted attributes.
     * @see Issue #42
     */
    public function testProductWithRemovedOption()
    {
        $product = $this->configurableProvider->create([
            ['color' => 'Black'],
            ['color' => 'Blue'],
        ]);

        // Remove one attribute
        $this->attributeProvider->deleteOption('color', 'Blue');

        // Ensure deleted attribute is not exported
        $feed = $this->exportFeed();
        $feed->getProduct($product->getId())->assertAttributes(['color' => 'Black']);
    }
}
