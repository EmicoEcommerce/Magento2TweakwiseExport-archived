<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Tweakwise\Magento2TweakwiseExport\TestHelper;

use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Tweakwise\Magento2TweakwiseExport\Test\TestCase;
use Tweakwise\Magento2TweakwiseExport\TestHelper\FeedData\CategoryData;
use Tweakwise\Magento2TweakwiseExport\TestHelper\FeedData\CategoryDataFactory;
use Tweakwise\Magento2TweakwiseExport\TestHelper\FeedData\ProductData;
use Tweakwise\Magento2TweakwiseExport\TestHelper\FeedData\ProductDataFactory;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;

class FeedData
{
    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var string
     */
    protected $feed;

    /**
     * @var TestCase
     */
    protected $test;

    /**
     * @var CategoryData[]
     */
    protected $categories;

    /**
     * @var ProductData[]
     */
    protected $products;

    /**
     * @var ProductDataFactory
     */
    protected $productDataFactory;

    /**
     * @var CategoryDataFactory
     */
    protected $categoryDataFactory;

    /**
     * FeedData constructor.
     *
     * @param Helper $helper
     * @param StoreManagerInterface $storeManager
     * @param ProductDataFactory $productDataFactory
     * @param CategoryDataFactory $categoryDataFactory
     * @param TestCase $test
     * @param string $feed
     */
    public function __construct(
        Helper $helper,
        StoreManagerInterface $storeManager,
        ProductDataFactory $productDataFactory,
        CategoryDataFactory $categoryDataFactory,
        TestCase $test,
        string $feed
    )
    {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->feed = $feed;
        $this->test = $test;
        $this->productDataFactory = $productDataFactory;
        $this->categoryDataFactory = $categoryDataFactory;
    }

    /**
     * @param int $entityId
     * @param string|null $storeCode
     * @return CategoryData
     */
    public function getCategory(int $entityId, string $storeCode = null): CategoryData
    {
        $this->parseCategories();

        $store = $this->getStore($storeCode);
        $tweakwiseId = $this->helper->getTweakwiseId($store->getId(), $entityId);

        if (!isset($this->categories[$tweakwiseId])) {
            $this->test->fail(sprintf('Could not find category for store %s with id %s', $store->getCode(), $entityId));
        }

        return $this->categories[$tweakwiseId];
    }

    /**
     * @param int $entityId
     * @param string|null $storeCode
     * @return ProductData
     */
    public function getProduct(int $entityId, string $storeCode = null): ProductData
    {
        $this->parseProducts();

        $store = $this->getStore($storeCode);
        $tweakwiseId = $this->helper->getTweakwiseId($store->getId(), $entityId);

        if (!isset($this->products[$tweakwiseId])) {
            $this->test->fail(sprintf('Could not find product for store %s with id %s', $store->getCode(), $entityId));
        }

        return $this->products[$tweakwiseId];
    }

    /**
     * @param int $entityId
     * @param string|null $storeCode
     */
    public function assertProductMissing(int $entityId, string $storeCode = null)
    {
        $this->parseProducts();

        $store = $this->getStore($storeCode);
        $tweakwiseId = $this->helper->getTweakwiseId($store->getId(), $entityId);

        if (!isset($this->products[$tweakwiseId])) {
            return;
        }

        $this->test->fail(sprintf(
            'Product for store %s with id %s was not supposed to be in de the feed.',
            $store->getCode(),
            $entityId
        ));
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return trim($this->feed);
    }

    /**
     * Parse category data
     */
    protected function parseCategories()
    {
        if ($this->categories === null) {
            return;
        }

        $this->categories = [];
        $this->products = [];

        $xml = simplexml_load_string($this->feed);
        foreach ($xml->xpath('//category') as $element) {
            $id = (string) $element->id;
            $this->products[$id] = $this->categoryDataFactory->create(['test' => $this->test, 'element' => $element]);
        }
    }

    /**
     * Parse product data
     */
    protected function parseProducts()
    {
        if ($this->products !== null) {
            return;
        }

        $this->products = [];

        $xml = simplexml_load_string($this->feed);
        foreach ($xml->xpath('//item') as $element) {
            $id = (string) $element->id;
            $this->products[$id] = $this->productDataFactory->create(['test' => $this->test, 'element' => $element]);
        }
    }

    /**
     * @param string|null $storeCode
     * @return StoreInterface
     */
    protected function getStore(string $storeCode = null): StoreInterface
    {
        if ($storeCode === null) {
            $store = $this->storeManager->getDefaultStoreView();
        } else {
            $store = $this->storeManager->getStore($storeCode);
        }

        if (!$store) {
            $this->test->fail('Default store not set and no store id provided.');
        }
        return $store;
    }
}
