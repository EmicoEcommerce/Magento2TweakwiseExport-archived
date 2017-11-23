<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Test\Integration\Export\Product;

use Emico\TweakwiseExport\Test\Integration\ExportTest;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use \Magento\CatalogInventory\Model\Configuration as StockConfiguration;

class StockTest extends ExportTest
{
    /**
     * Test export with one product and check on product data
     */
    public function testEnableStockManagement()
    {
        $productInStock = $this->createProduct();
        $productOutStock = $this->createProduct([], 0);

        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, true);

        $feed = $this->exportFeed();

        $this->assertProductData($feed, $productInStock->getSku());
        $this->assertNull($this->getProductItem($feed, $productOutStock->getSku()));
    }

    /**
     * Test export with one product and check on product data
     */
    public function testDisableStockManagement()
    {
        $product = $this->createProduct([], 0);
        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, false);

        $feed = $this->exportFeed();
        $this->assertProductData($feed, $product->getSku());
    }

    /**
     * - Product with qty > 0 but less then configured qty threshold should not be exported.
     * - Product with qty > qty threshold should be exported.
     */
    public function testInStockWithQtyThreshold()
    {
        $productInStock = $this->createProduct([], 6);
        $productOutStock = $this->createProduct([], 4);

        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, true);
        $this->setConfig(StockConfiguration::XML_PATH_STOCK_THRESHOLD_QTY, 5);

        $feed = $this->exportFeed();

        $this->assertProductData($feed, $productInStock->getSku());
        $this->assertNull($this->getProductItem($feed, $productOutStock->getSku()));
    }

    /**
     * - Product with qty < General qty threshold but qty threshold on product < qty should be exported.
     * - Product with qty > General qty threshold but qty threshold on product > qty should not be exported.
     */
    public function testInStockWithQtyThresholdOnProduct()
    {
        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, true);
        $this->setConfig(StockConfiguration::XML_PATH_STOCK_THRESHOLD_QTY, 10);

        $productInStock = $this->createProduct([], 6);
        $this->updateStockItem($productInStock, function(StockItemInterface $stockItem) {
            $stockItem->setUseConfigMinQty(false);
            $stockItem->setMinQty(5);
        });
        $productOutStock = $this->createProduct([], 4);
        $this->updateStockItem($productOutStock, function(StockItemInterface $stockItem) {
            $stockItem->setUseConfigMinQty(false);
            $stockItem->setMinQty(5);
        });

        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, true);
        $this->setConfig(StockConfiguration::XML_PATH_STOCK_THRESHOLD_QTY, 5);

        $feed = $this->exportFeed();

        $this->assertProductData($feed, $productInStock->getSku());
        $this->assertNull($this->getProductItem($feed, $productOutStock->getSku()));
    }
}