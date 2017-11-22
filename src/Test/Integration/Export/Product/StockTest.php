<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Test\Integration\Export\Product;

use Emico\TweakwiseExport\Test\Integration\ExportTest;
use \Magento\CatalogInventory\Model\Configuration as StockConfiguration;

class StockTest extends ExportTest
{
    /**
     * Test export with one product and check on product data
     */
    public function testEnableStockManagement()
    {
        $productInStock = $this->createSavedProduct();
        $productOutStock = $this->createSavedProduct(0);

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
        $product = $this->createSavedProduct(0);
        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, false);

        $feed = $this->exportFeed();
        $this->assertProductData($feed, $product->getSku());
    }
}