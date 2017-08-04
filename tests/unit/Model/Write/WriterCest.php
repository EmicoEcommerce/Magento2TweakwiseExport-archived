<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Model\Write\Writer;

use ArrayIterator;
use DateTime;
use DOMDocument;
use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Helper;
use Emico\TweakwiseExport\Model\Logger;
use Emico\TweakwiseExport\Model\Write\Categories;
use Emico\TweakwiseExport\Model\Write\EavIterator;
use Emico\TweakwiseExport\Model\Write\Products;
use Emico\TweakwiseExport\Model\Write\Products\Iterator;
use Emico\TweakwiseExport\Model\Write\Writer;
use Exception;
use FunctionalTester;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\App\State as AppState;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;
use Mockery;
use Mockery\MockInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as Monolog;

class WriterCest
{
    /**
     * @param string $name
     * @return Store|MockInterface
     */
    protected function createStore($name)
    {
        /** @var Store|MockInterface $store */
        $store = Mockery::mock(Store::class);
        $store->shouldReceive('getName')->atLeast(0)->andReturn($name);

        $code = str_replace(' ', '_', $name);
        $code = strtolower($code);
        $store->shouldReceive('getCode')->atLeast(0)->andReturn($code);

        static $storeIndex;
        $store->shouldReceive('getId')->atLeast(0)->andReturn($storeIndex++);
        $store->shouldReceive('getRootCategoryId')->atLeast(0)->andReturn($storeIndex);

        return $store;
    }

    /**
     * @param EavIterator $categoryIterator
     * @param Iterator $productIterator
     * @return Writer
     */
    protected function getWriter(EavIterator $categoryIterator, Iterator $productIterator)
    {
        $defaultStore = $this->createStore('Default Store');

        /** @var StoreManager|MockInterface $storeManager */
        $storeManager = Mockery::mock(StoreManager::class);
        $storeManager->shouldReceive('getDefaultStoreView')->atLeast(0)->andReturn($defaultStore);
        $storeManager->shouldReceive('getStores')->atLeast(0)->andReturn([$defaultStore]);

        /** @var AppState|MockInterface $appState */
        $appState = Mockery::mock(AppState::class);
        $appState->shouldReceive('getMode')->atLeast(0)->andReturn(AppState::MODE_DEVELOPER);

        /** @var Config|MockInterface $config */
        $config = Mockery::mock(Config::class);
        $config->shouldReceive('isEnabled')->andReturn(true);

        /** @var EavConfig|MockInterface $eavConfig */
        $eavConfig = Mockery::mock(EavConfig::class);

        $helper = new Helper();
        $logger = new Monolog('log');
        $logger->pushHandler(new StreamHandler('php://stdout'));

        $log = new Logger($logger);

        $writer = new Writer($storeManager, $appState, [
            new Categories($categoryIterator, $storeManager, $config, $helper, $log),
            new Products($config, $productIterator, $storeManager, $helper, $log, $eavConfig),
        ]);
        $writer->setNow(DateTime::createFromFormat('Y-m-d\TH:i:s.uP', '2017-01-01T00:00:00.000000+00:00'));

        return $writer;
    }

    /**
     * @param EavIterator $categoryIterator
     * @param Iterator $productIterator
     * @return string
     * @throws Exception
     */
    protected function getWriterXml(EavIterator $categoryIterator, Iterator $productIterator)
    {
        $writer = $this->getWriter($categoryIterator, $productIterator);

        $stream = fopen('php://memory', 'w+');
        if (!$stream) {
            throw new Exception('Could not open memory stream.');
        }

        try {
            $writer->write($stream);
            rewind($stream);
            return stream_get_contents($stream);
        } finally {
            fclose($stream);
        }
    }

    /**
     * @param array $data
     * @return EavIterator|MockInterface
     */
    protected function createCategoryIterator(array $data)
    {
        /** @var EavIterator|MockInterface $categoryIterator */
        $categoryIterator = Mockery::mock(EavIterator::class);
        $categoryIterator->shouldReceive('setStoreId')->atLeast(0)->andReturnSelf();
        $categoryIterator->shouldReceive('getIterator')->andReturn(new ArrayIterator($data));

        return $categoryIterator;
    }

    /**
     * @param array $data
     * @return Iterator|MockInterface
     */
    protected function createProductIterator(array $data)
    {
        /** @var Iterator|MockInterface $productIterator */
        $productIterator = Mockery::mock(Iterator::class);
        $productIterator->shouldReceive('setStoreId')->atLeast(0)->andReturnSelf();
        $productIterator->shouldReceive('getIterator')->andReturn(new ArrayIterator($data));

        return $productIterator;
    }

    /**
     * @param string $xml
     * @return string
     */
    protected function normalizeXml($xml)
    {
        $doc = new DOMDocument(1.0);
        $doc->preserveWhiteSpace = false;
        $doc->formatOutput = true;
        $doc->loadXML($xml);
        return $doc->saveHTML();
    }

    /**
     * @param FunctionalTester $i
     * @param array $categoryData
     * @param array $productData
     * @param string $file
     */
    protected function assertXmlResult(FunctionalTester $i, array $categoryData, array $productData, $file)
    {
        $categoryIterator = $this->createCategoryIterator($categoryData);
        $productIterator = $this->createProductIterator($productData);

        $actual = $this->getWriterXml($categoryIterator, $productIterator);
        $actual = $this->normalizeXml($actual);

        $expected = $i->data()->read('unit/Model/Write/Writer/' . $file);
        $expected = $this->normalizeXml($expected);

        $i->assertEquals($expected, $actual);
    }

    /**
     * @param FunctionalTester $i
     */
    public function testEmptyExport(FunctionalTester $i)
    {
        $this->assertXmlResult($i, [], [], 'empty-export.xml');
    }
}
