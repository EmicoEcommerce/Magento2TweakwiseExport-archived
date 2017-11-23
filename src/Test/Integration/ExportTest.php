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
use Emico\TweakwiseExport\TestHelper\Data\CategoryProvider;
use Emico\TweakwiseExport\TestHelper\Data\ProductProvider;
use Emico\TweakwiseExport\TestHelper\FeedData;
use SimpleXMLElement;

abstract class ExportTest extends TestCase
{
    /**
     * @var Writer
     */
    protected $writer;

    /**
     * @var ProductProvider
     */
    protected $productData;

    /**
     * @var CategoryProvider
     */
    protected $categoryData;

    /**
     * @var FeedData
     */
    protected $feedData;

    /**
     * Make sure export is enabled and set some much used objects
     */
    protected function setUp()
    {
        parent::setUp();

        $this->setConfig(Config::PATH_ENABLED, true);

        $this->productData = $this->getObject(ProductProvider::class);
        $this->categoryData = $this->getObject(CategoryProvider::class);
        $this->feedData = $this->getObject(FeedData::class);

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
        $productData = $this->feedData->getProductData($feed, $sku);
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