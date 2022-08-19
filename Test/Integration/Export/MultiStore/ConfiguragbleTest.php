<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Tweakwise\Magento2TweakwiseExport\Test\Integration\Export\MultiStore;

use Tweakwise\Magento2TweakwiseExport\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Test\Integration\Export\MultiStoreTest;
use Tweakwise\Magento2TweakwiseExport\TestHelper\Data\Product\ConfigurableProvider;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\CatalogInventory\Model\Configuration as StockConfiguration;

/**
 * Class BasicTest
 *
 * @package Tweakwise\Magento2TweakwiseExport\Test\Integration\Export\Product
 *
 * @magentoDataFixtureBeforeTransaction createMultiStoreFixture
 * @magentoDbIsolation enabled
 */
class ConfiguragbleTest extends MultiStoreTest
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
     * Test multiple stores enabled
     */
    public function testConfigurableNotExportedWhenChildrenDisabledSingleStore()
    {
        $this->setConfig(StockConfiguration::XML_PATH_MANAGE_STOCK, true);
        $this->setConfig(StockConfiguration::XML_PATH_SHOW_OUT_OF_STOCK, false);
        $this->setConfig(Config::PATH_OUT_OF_STOCK_CHILDREN, false);

        /** @var Product $product */
        $product = $this->configurableProvider->create([
            ['color' => 'black', 'qty' => 10],
            ['color' => 'blue', 'qty' => 10],
        ]);

        /** @var Product $configurableProduct */
        foreach ($product->getData(ConfigurableProvider::GENERATED_CHILD_PRODUCTS) as $configurableProduct) {
            $this->productData->saveAttribute($configurableProduct, 'status', Status::STATUS_DISABLED, self::STORE_STORE_CODE);
        }

        $feed = $this->exportFeed();
        $feed->getProduct($product->getId());
        $feed->assertProductMissing($product->getId(), self::STORE_STORE_CODE);
    }
}
