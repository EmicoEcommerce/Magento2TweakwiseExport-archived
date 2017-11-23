<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Test\Integration\Export\Product;

use Emico\TweakwiseExport\Model\Config;
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
     * Test to see if show out of stock children is handled when set to true
     *
     * @magentoDbIsolation enabled
     */
    public function testAttributesVisibleWhenOutStock()
    {
        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, true);
        $this->setConfig(StockConfiguration::XML_PATH_SHOW_OUT_OF_STOCK, false);
        $this->setConfig(Config::PATH_OUT_OF_STOCK_CHILDREN, true);

        $product = $this->configurableProvider->create([
            ['color' => 'black', 'qty' => 0],
            ['color' => 'blue', 'qty' => 10],
            ['color' => 'white', 'qty' => 2],
        ]);

        $feed = $this->exportFeed();
        $this->assertProductData($feed, $product->getSku(), null, null, ['color' => ['black', 'blue', 'white']]);
    }

    /**
     * Test to see if show out of stock children is handled when set to false
     *
     * @magentoDbIsolation enabled
     */
    public function testAttributesNotVisibleWhenOutStock()
    {
        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, true);
        $this->setConfig(StockConfiguration::XML_PATH_SHOW_OUT_OF_STOCK, false);
        $this->setConfig(Config::PATH_OUT_OF_STOCK_CHILDREN, false);

        $product = $this->configurableProvider->create([
            ['color' => 'black', 'qty' => 0],
            ['color' => 'blue', 'qty' => 10],
            ['color' => 'white', 'qty' => 2],
        ]);

        $feed = $this->exportFeed();
        $this->assertProductData($feed, $product->getSku(), null, null, ['color' => ['blue', 'white']]);
    }

    /**
     * When product specific configuration is set check if child product will not be exported.
     *
     * @magentoDbIsolation enabled
     */
    public function testAttributesNotVisibleWhenOutStockWithProductSpecificConfiguration()
    {
        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, true);
        $this->setConfig(StockConfiguration::XML_PATH_SHOW_OUT_OF_STOCK, false);
        $this->setConfig(Config::PATH_OUT_OF_STOCK_CHILDREN, false);

        $product = $this->configurableProvider->create([
            ['color' => 'black', 'qty' => 0],
            ['color' => 'blue', 'qty' => 10],
            ['color' => 'white', 'qty' => 2, 'use_config_min_qty' => false, 'min_qty' => 5],
        ]);

        $feed = $this->exportFeed();
        $this->assertProductData($feed, $product->getSku(), null, null, ['color' => ['blue']]);
    }

    /**
     * When product specific configuration is set check and all children are not valid check if entire product will be skipped.
     *
     * @magentoDbIsolation enabled
     */
    public function testProductNotExportWhenOutStockWithProductSpecificConfiguration()
    {
        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, true);
        $this->setConfig(StockConfiguration::XML_PATH_SHOW_OUT_OF_STOCK, false);
        $this->setConfig(Config::PATH_OUT_OF_STOCK_CHILDREN, false);

        $product = $this->configurableProvider->create([
            ['color' => 'black', 'qty' => 0],
            ['color' => 'white', 'qty' => 2, 'use_config_min_qty' => false, 'min_qty' => 5],
        ]);

        $feed = $this->exportFeed();
        $this->assertNull($this->feedData->getProductData($feed, $product->getSku()));
    }
}