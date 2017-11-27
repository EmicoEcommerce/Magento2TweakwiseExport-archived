<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Test\Integration\Export\Product;

use Emico\TweakwiseExport\Test\Integration\ExportTest;
use Magento\CatalogInventory\Model\Configuration as StockConfiguration;

class StockTest extends ExportTest
{
    /**
     * Test export with one product and check on product data
     *
     * @magentoDbIsolation enabled
     */
    public function testEnableStockManagement()
    {
        $productInStock = $this->productData->create();
        $productOutStock = $this->productData->create(['qty' => 0]);

        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, true);
        $this->setConfig(StockConfiguration::XML_PATH_SHOW_OUT_OF_STOCK, false);

        $feed = $this->exportFeed();
        $feed->getProduct($productInStock->getId());
        $feed->assertProductMissing($productOutStock->getId());
    }

    /**
     * Test export with one product and check on product data
     *
     * @magentoDbIsolation enabled
     */
    public function testEnableStockManagementShowOutOfStockProducts()
    {
        $productOutStock = $this->productData->create(['qty' => 0]);

        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, true);
        $this->setConfig(StockConfiguration::XML_PATH_SHOW_OUT_OF_STOCK, true);

        $feed = $this->exportFeed();
        $feed->getProduct($productOutStock->getId());
    }

    /**
     * Test export with one product and check on product data
     *
     * @magentoDbIsolation enabled
     */
    public function testDisableStockManagement()
    {
        $product = $this->productData->create(['qty' => 0]);
        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, false);
        $this->setConfig(StockConfiguration::XML_PATH_SHOW_OUT_OF_STOCK, false);

        $feed = $this->exportFeed();
        $feed->getProduct($product->getId());
    }

    /**
     * - Product with qty > 0 but less then configured qty threshold should not be exported.
     * - Product with qty > qty threshold should be exported.
     *
     * @magentoDbIsolation enabled
     */
    public function testInStockWithQtyThreshold()
    {
        $productInStock = $this->productData->create(['qty' => 6]);
        $productOutStock = $this->productData->create(['qty' => 4]);

        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, true);
        $this->setConfig(StockConfiguration::XML_PATH_SHOW_OUT_OF_STOCK, false);
        $this->setConfig(StockConfiguration::XML_PATH_STOCK_THRESHOLD_QTY, 5);

        $feed = $this->exportFeed();
        $feed->getProduct($productInStock->getId());
        $feed->assertProductMissing($productOutStock->getId());
    }

    /**
     * - Product with qty < General qty threshold but qty threshold on product < qty should be exported.
     * - Product with qty > General qty threshold but qty threshold on product > qty should not be exported.
     *
     * @magentoDbIsolation enabled
     */
    public function testInStockWithQtyThresholdOnProduct()
    {
        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, true);
        $this->setConfig(StockConfiguration::XML_PATH_SHOW_OUT_OF_STOCK, false);
        $this->setConfig(StockConfiguration::XML_PATH_STOCK_THRESHOLD_QTY, 5);

        $productInStock = $this->productData->create(['qty' => 6, 'use_config_min_qty' => false, 'min_qty' => 5]);
        $productOutStock = $this->productData->create(['qty' => 4, 'use_config_min_qty' => false, 'min_qty' => 5]);

        $feed = $this->exportFeed();
        $feed->getProduct($productInStock->getId());
        $feed->assertProductMissing($productOutStock->getId());
    }
}