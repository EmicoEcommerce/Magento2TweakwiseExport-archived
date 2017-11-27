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
    private $attributeProvider;

    /**
     * @var ConfigurableProvider
     */
    private $configurableProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
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
        $product = $this->configurableProvider->create([
            ['color' => 'black', 'status' => Status::STATUS_ENABLED],
            ['color' => 'blue', 'status' => Status::STATUS_ENABLED],
            ['color' => 'white', 'status' => Status::STATUS_DISABLED],
        ]);

        $this->exportFeed()->getProduct($product->getId())->assertAttributes(['color' => ['black', 'blue']]);
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

        $this->exportFeed()->getProduct($product->getId())->assertAttributes(['color' => ['child color', 'parent color']]);
    }
}