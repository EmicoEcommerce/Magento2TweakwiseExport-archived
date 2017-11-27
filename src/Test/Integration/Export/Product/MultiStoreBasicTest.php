<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Test\Integration\Export\Product;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Helper;
use Emico\TweakwiseExport\Test\Integration\ExportTest;
use Emico\TweakwiseExport\TestHelper\Data\StoreProvider;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\TestFramework\Helper\Bootstrap;

/**
 * Class MultiStoreBasicTest
 *
 * @package Emico\TweakwiseExport\Test\Integration\Export\Product
 *
 * @magentoDataFixtureBeforeTransaction createMultiStoreFixture
 * @magentoDbIsolation enabled
 */
class MultiStoreBasicTest extends ExportTest
{
    /**
     * Store codes for second store
     */
    const STORE_WEBSITE_CODE = 'website_2';
    const STORE_GROUP_CODE = 'group_2';
    const STORE_STORE_CODE = 'store_2';

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var StoreProvider
     */
    private $storeProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->storeProvider = $this->getObject(StoreProvider::class);
        $this->helper = $this->getObject(Helper::class);

        $this->setConfig(Config::PATH_ENABLED, true, self::STORE_STORE_CODE);
    }

    /**
     * Fixture for creating multi store
     */
    public static function createMultiStoreFixture()
    {
        /** @var StoreProvider $storeProvider */
        $storeProvider = Bootstrap::getObjectManager()->create(StoreProvider::class);

        $website = $storeProvider->createWebsite(['code' => self::STORE_WEBSITE_CODE]);
        $group = $storeProvider->createStoreGroup($website, ['code' => self::STORE_GROUP_CODE]);
        $storeProvider->createStoreView($group, ['code' => self::STORE_STORE_CODE]);
    }

    /**
     * Fixture for rollback multi store
     */
    public static function createMultistoryFixtureRollback()
    {
        /** @var StoreProvider $storeProvider */
        $storeProvider = Bootstrap::getObjectManager()->create(StoreProvider::class);

        $storeProvider->removeStoreView(self::STORE_STORE_CODE);
        $storeProvider->removeStoreGroup(self::STORE_GROUP_CODE);
        $storeProvider->removeWebsite(self::STORE_WEBSITE_CODE);
    }

    /**
     * Test multiple stores enabled
     */
    public function testEnabled()
    {
        $product = $this->productData->create();

        $feed = $this->exportFeed();
        $feed->getProduct($product->getId());
        $feed->getProduct($product->getId(), self::STORE_STORE_CODE);
    }

    /**
     * Test if feed will not be exported for disabled store
     */
    public function testDisabledStore()
    {
        $this->setConfig(Config::PATH_ENABLED, false);

        $product = $this->productData->create();

        $feed = $this->exportFeed();
        $feed->assertProductMissing($product->getId());
        $feed->getProduct($product->getId(), self::STORE_STORE_CODE);
    }

    /**
     * Test if product will be skipped if disabled in store
     */
    public function testDisabledProductForOneStore()
    {
        $product = $this->productData->create();

        $this->productData->saveAttribute($product, 'status', Status::STATUS_DISABLED, self::STORE_STORE_CODE);

        $feed = $this->exportFeed();
        $feed->getProduct($product->getId());
        $feed->assertProductMissing($product->getId(), self::STORE_STORE_CODE);
    }
}