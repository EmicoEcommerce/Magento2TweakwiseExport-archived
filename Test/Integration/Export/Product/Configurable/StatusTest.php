<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Test\Integration\Export\Product\Configurable;

use Emico\TweakwiseExport\Test\Integration\ExportTest;
use Emico\TweakwiseExport\TestHelper\Data\Product\ConfigurableProvider;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

/**
 * @magentoDbIsolation enabled
 */
class StatusTest extends ExportTest
{
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
     * Test to see if product will not be exported if all simples are disabled
     */
    public function testWhenAllSimpleDisabled()
    {
        $product = $this->configurableProvider->create([
            ['color' => 'black', 'status' => Status::STATUS_DISABLED],
            ['color' => 'blue', 'status' => Status::STATUS_DISABLED],
            ['color' => 'white', 'status' => Status::STATUS_DISABLED],
        ]);

        $this->exportFeed()->assertProductMissing($product->getId());
    }
}
