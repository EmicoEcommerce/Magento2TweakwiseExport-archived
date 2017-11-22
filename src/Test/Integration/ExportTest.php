<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Test\Integration;

use DateTime;
use DOMDocument;
use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Export;
use Emico\TweakwiseExport\Model\Write\Writer;
use Faker\Factory;
use Faker\Generator;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Api\CategoryLinkManagementInterface;

abstract class ExportTest extends TestCase
{
    /**
     * @var Generator
     */
    protected $faker;

    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var ProductInterfaceFactory
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
     * Make sure export is enabled
     */
    protected function setUp()
    {
        $this->setConfig(Config::PATH_ENABLED, true);

        $this->faker = Factory::create();
        $this->productRepository = $this->getObject(ProductRepositoryInterface::class);
        $this->productFactory = $this->getObject(ProductInterfaceFactory::class);
        $this->categoryRepository = $this->getObject(CategoryRepositoryInterface::class);
        $this->categoryFactory = $this->getObject(CategoryFactory::class);
        $this->categoryLinkManagement = $this->getObject(CategoryLinkManagementInterface::class);
        $this->writer = $this->getObject(Writer::class);
        $this->writer->setNow(DateTime::createFromFormat('Y-d-m H:i:s', '2017-01-01 00:00:00'));
    }

    /**
     * @return Export
     */
    protected function getExporter(): Export
    {
        return $this->getObject(Export::class);
    }

    /**
     * @return string
     */
    protected function exportFeed(): string
    {
        $resource = fopen('php://temp/maxmemory:' . (256 * 1024 * 1023), 'wb+');
        if (!is_resource($resource)) {
            $this->fail('Could not create memory resource for export');
        }

        try {
            $this->getExporter()->generateFeed($resource);
            rewind($resource);
            return stream_get_contents($resource);
        } finally {
            fclose($resource);
        }
    }

    /**
     * @param string $file
     * @param string|null $result
     */
    protected function assertFeedResult(string $file, string $result = null)
    {
        if ($result === null) {
            $result = $this->exportFeed();
        }

        $file = __DIR__ . '/../../../tests/data/' . ltrim($file, '/');
        $this->assertStringEqualsFile($file, trim($result));
    }

    /**
     * @return ProductInterface
     */
    protected function createProduct(): ProductInterface
    {
        /** @var ProductInterface $product */
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
}