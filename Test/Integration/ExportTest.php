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
use Emico\TweakwiseExport\TestHelper\FeedDataFactory;
use Magento\Eav\Model\Config as EavConfig;

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
     * @var FeedDataFactory
     */
    protected $feedDataFactory;

    /**
     * Make sure export is enabled and set some much used objects
     */
    protected function setUp()
    {
        parent::setUp();

        $this->setConfig(Config::PATH_ENABLED, true);

        $this->productData = $this->getObject(ProductProvider::class);
        $this->categoryData = $this->getObject(CategoryProvider::class);
        $this->feedDataFactory = $this->getObject(FeedDataFactory::class);

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
     * @return EavConfig
     */
    protected function getEavConfig(): EavConfig
    {
        return $this->getObject(EavConfig::class);
    }

    /**
     * @return FeedData
     */
    protected function exportFeed(): FeedData
    {
        $resource = fopen('php://temp/maxmemory:' . (256 * 1024 * 1023), 'wb+');
        if (!is_resource($resource)) {
            $this->fail('Could not create memory resource for export');
        }

        $this->getEavConfig()->clear();

        try {
            $this->getExporter()->generateFeed($resource);
            rewind($resource);
            $feed = stream_get_contents($resource);

            return $this->feedDataFactory->create(['test' => $this, 'feed' => $feed]);
        } finally {
            fclose($resource);
        }
    }
}