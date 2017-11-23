<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Test\Integration\Export\Product;

use Emico\TweakwiseExport\Test\Integration\ExportTest;
use Emico\TweakwiseExport\TestHelper\Data\Product\ConfigurableProvider;
use Magento\CatalogInventory\Model\Configuration as StockConfiguration;

class ConfigurableStockTest extends ExportTest
{
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
    }

    /**
     * Test export with one product and check on product data
     *
     * @magentoDbIsolation enabled
     */
    public function testAttributesNotVisibleWhenOutStock()
    {
        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, true);

        $product = $this->configurableProvider->create([
            ['color' => 'black', 'qty' => 0],
            ['color' => 'blue', 'qty' => 10],
            ['color' => 'white', 'qty' => 2],
        ]);

        $feed = $this->exportFeed();
        $this->assertProductData($feed, $product->getSku(), null, null, ['color' => ['blue', 'white']]);
    }
}