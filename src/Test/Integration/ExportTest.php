<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Test\Integration;

use DateTime;
use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Export;
use Emico\TweakwiseExport\Model\Write\Writer;
use Faker\Factory;
use Faker\Generator;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ProductRepository;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\Api\SearchCriteria;
use SimpleXMLElement;

abstract class ExportTest extends TestCase
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * @var ProductFactory
     */
    protected $productFactory;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var CategoryLinkManagementInterface
     */
    protected $categoryLinkManagement;

    /**
     * @var Writer
     */
    protected $writer;

    /**
     * @var StockRegistryInterface
     */
    protected $stockRegistry;

    /**
     * Make sure export is enabled and set some much used objects
     */
    protected function setUp()
    {
        parent::setUp();

        $this->setConfig(Config::PATH_ENABLED, true);

        $this->faker = Factory::create();
        $this->productRepository = $this->getObject(ProductRepository::class);
        $this->productFactory = $this->getObject(ProductFactory::class);
        $this->categoryRepository = $this->getObject(CategoryRepositoryInterface::class);
        $this->categoryFactory = $this->getObject(CategoryFactory::class);
        $this->categoryLinkManagement = $this->getObject(CategoryLinkManagementInterface::class);
        $this->stockRegistry = $this->getObject(StockRegistryInterface::class);

        $this->writer = $this->getObject(Writer::class);
        $this->writer->setNow(DateTime::createFromFormat('Y-d-m H:i:s', '2017-01-01 00:00:00'));

        $this->clearTestData();
    }

    /**
     * Remove all created test products
     */
    protected function tearDown()
    {
        parent::tearDown();

        $this->clearTestData();
    }

    /**
     * Clear old test data
     */
    protected function clearTestData()
    {
        /** @var SearchCriteria $productCriteria */
        $productCriteria = $this->createObject(SearchCriteria::class);
        $products = $this->productRepository->getList($productCriteria)->getItems();
        foreach ($products as $product) {
            $this->productRepository->delete($product);
        }
    }

    /**
     * @return Export
     */
    protected function getExporter(): Export
    {
        return $this->getObject(Export::class);
    }

    /**
     * @return SimpleXMLElement
     */
    protected function exportFeed(): SimpleXMLElement
    {
        $resource = fopen('php://temp/maxmemory:' . (256 * 1024 * 1023), 'wb+');
        if (!is_resource($resource)) {
            $this->fail('Could not create memory resource for export');
        }

        try {
            $this->getExporter()->generateFeed($resource);
            rewind($resource);
            $xml = stream_get_contents($resource);
            return simplexml_load_string($xml);
        } finally {
            fclose($resource);
        }
    }

    /**
     * @return Product
     */
    protected function createProduct(): Product
    {
        /** @var Product $product */
        $product = $this->productFactory->create();
        $product->setSku('test-' . $this->faker->uuid);
        $product->setName($this->faker->name);
        $product->setTypeId(Product\Type::TYPE_SIMPLE);
        $product->setVisibility(Product\Visibility::VISIBILITY_BOTH);
        $product->setPrice($this->faker->randomNumber(2));
        $product->setAttributeSetId(4); // Default attribute set for products
        $product->setStatus(Product\Attribute\Source\Status::STATUS_ENABLED);

        return $product;
    }

    /**
     * @param array $categoryIds
     * @param int $qty
     * @return Product
     */
    protected function createSavedProduct(int $qty = 100, array $categoryIds = [2]): Product
    {
        $product = $this->createProduct();
        $this->productRepository->save($product);
        $this->categoryLinkManagement->assignProductToCategories($product->getSku(), $categoryIds);

        $stockItem = $this->stockRegistry->getStockItemBySku($product->getSku());
        $stockItem->setQty($qty);
        $this->stockRegistry->updateStockItemBySku($product->getSku(), $stockItem);

        return $product;
    }

    /**
     * @param SimpleXMLElement $feed
     * @param string $sku
     * @return array|null
     */
    protected function getProductItem(SimpleXMLElement $feed, string $sku)
    {
        foreach ($feed->xpath('//item') as $element) {
            $data = [
                'xml' => $element,
                'id' => (string) $element->id,
                'name' => (string) $element->name,
                'price' => (float) $element->price,
                'attributes' => [],
                'categories' => [],
            ];

            foreach ($element->attributes->children() as $attributeElement) {
                $name = (string) $attributeElement->name;
                $value = (string) $attributeElement->value;
                $data['attributes'][$name] = $value;
            }

            foreach ($element->categories->children() as $categoryElement) {
                $data['categories'][] = (string) $categoryElement;
            }

            $key = $data['attributes']['sku'] ?? $data['id'];
            if ($key === $sku) {
                return $data;
            }
        }

        return null;
    }

    /**
     * @param SimpleXMLElement $feed
     * @param string $sku
     * @param string $attribute
     * @return null|string
     */
    protected function getProductAttribute(SimpleXMLElement $feed, string $sku, string $attribute)
    {
        $item = $this->getProductItem($feed, $sku);
        if (!$item) {
            return null;
        }

        if (!isset($item['attributes'][$attribute])) {
            return null;
        }

        return $item['attributes'][$attribute];
    }

    /**
     * @param string $file
     * @param SimpleXMLElement $result
     */
    protected function assertFeedResult(string $file, SimpleXMLElement $result = null)
    {
        if ($result === null) {
            $result = $this->exportFeed();
        }

        $file = __DIR__ . '/../../../tests/data/' . ltrim($file, '/');
        $this->assertStringEqualsFile($file, trim($result->asXML()));
    }

    /**
     * @param SimpleXMLElement $feed
     * @param string|null $sku
     * @param string|null $name
     * @param float|null $price
     * @param array|null $attributes
     * @param array|null $categories
     */
    protected function assertProductData(
        SimpleXMLElement $feed,
        string $sku,
        string $name = null,
        float $price = null,
        array $attributes = null,
        array $categories = null
    )
    {
        $productData = $this->getProductItem($feed, $sku);
        $this->assertNotNull($productData);

        if ($price !== null) {
            $this->assertArrayHasKey('price', $productData);
            $this->assertEquals($price, $productData['price']);
        }

        if ($name !== null) {
            $this->assertArrayHasKey('name', $productData);
            $this->assertEquals($name, $productData['name']);
        }

        if ($attributes !== null) {
            foreach ($attributes as $key => $value) {
                $this->assertArrayHasKey($key, $productData['attributes']);
                $this->assertEquals($value, $productData['attributes'][$key]);
            }
        }

        if ($categories !== null) {
            $this->assertArraySubset($categories, $productData['categories']);
        }
    }
}