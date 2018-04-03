<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Test\Integration\Export\Product;

use Emico\TweakwiseExport\Test\Integration\ExportTest;
use Emico\TweakwiseExport\TestHelper\Data\Product\AttributeProvider;
use Emico\TweakwiseExport\TestHelper\Data\Product\ConfigurableProvider;

class AttributeOptionTest extends ExportTest
{
    /**
     * @var ConfigurableProvider
     */
    private $configurableProvider;

    /**
     * @var AttributeProvider
     */
    private $attributeProvider;

    /**
     * Make sure export is enabled and set some much used objects
     */
    protected function setUp()
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
        $feed->getProduct($product->getId())->assertAttributes(['color' => ['Black']]);
    }
}