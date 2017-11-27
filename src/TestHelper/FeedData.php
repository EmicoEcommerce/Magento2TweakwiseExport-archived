<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\TestHelper;

use Emico\TweakwiseExport\Model\Helper;
use Emico\TweakwiseExport\Test\TestCase;
use Emico\TweakwiseExport\TestHelper\FeedData\CategoryData;
use Emico\TweakwiseExport\TestHelper\FeedData\CategoryDataFactory;
use Emico\TweakwiseExport\TestHelper\FeedData\ProductData;
use Emico\TweakwiseExport\TestHelper\FeedData\ProductDataFactory;
use Magento\Store\Model\StoreManagerInterface;

class FeedData
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var string
     */
    private $feed;

    /**
     * @var TestCase
     */
    private $test;

    /**
     * @var CategoryData[]
     */
    private $categories;

    /**
     * @var ProductData[]
     */
    private $products;

    /**
     * @var ProductDataFactory
     */
    private $productDataFactory;

    /**
     * @var CategoryDataFactory
     */
    private $categoryDataFactory;

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
     * @param int|null $storeId
     * @return CategoryData
     */
    public function getCategory(int $entityId, int $storeId = null): CategoryData
    {
        $this->parseCategories();

        if ($storeId === null) {
            $storeId = $this->getDefaultStoreId();
        }

        $tweakwiseId = $this->helper->getTweakwiseId($storeId, $entityId);
        if (!isset($this->categories[$tweakwiseId])) {
            $this->test->fail(sprintf('Could not find category for store %s with id %s', $storeId, $entityId));
        }

        return $this->categories[$tweakwiseId];
    }

    /**
     * @param int $entityId
     * @param int|null $storeId
     * @return ProductData
     */
    public function getProduct(int $entityId, int $storeId = null): ProductData
    {
        $this->parseProducts();

        if ($storeId === null) {
            $storeId = $this->getDefaultStoreId();
        }

        $tweakwiseId = $this->helper->getTweakwiseId($storeId, $entityId);
        if (!isset($this->products[$tweakwiseId])) {
            $this->test->fail(sprintf('Could not find product for store %s with id %s', $storeId, $entityId));
        }

        return $this->products[$tweakwiseId];
    }

    /**
     * @param int $entityId
     * @param int|null $storeId
     */
    public function assertProductMissing(int $entityId, int $storeId = null)
    {
        $this->parseProducts();

        if ($storeId === null) {
            $storeId = $this->getDefaultStoreId();
        }

        $tweakwiseId = $this->helper->getTweakwiseId($storeId, $entityId);
        if (!isset($this->products[$tweakwiseId])) {
            return;
        }

        $this->test->fail(sprintf(
            'Product for store %s with id %s was not supposed to be in de the feed.',
            $this->getStoreCode($storeId),
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
    private function parseCategories()
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
    private function parseProducts()
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
     * @return int
     */
    private function getDefaultStoreId(): int
    {
        $defaultStore = $this->storeManager->getDefaultStoreView();
        if (!$defaultStore) {
            $this->test->fail('Default store not set and no store id provided.');
        }
        return (int) $defaultStore->getId();
    }

    /**
     * @param int $storeId
     * @return string
     */
    private function getStoreCode(int $storeId): string
    {
        return $this->storeManager->getStore($storeId)->getCode();
    }
}