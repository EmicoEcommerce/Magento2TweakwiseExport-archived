<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Model\Write;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Helper;
use Emico\TweakwiseExport\Model\Logger;
use Emico\TweakwiseExport\Model\Write\Products\Iterator;
use Magento\Framework\Profiler;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;

class Products implements WriterInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Iterator
     */
    protected $iterator;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * Products constructor.
     *
     * @param Config $config
     * @param Iterator $iterator
     * @param StoreManager $storeManager
     * @param Helper $helper
     * @param Logger $log
     */
    public function __construct(Config $config, Iterator $iterator, StoreManager $storeManager, Helper $helper, Logger $log)
    {
        $this->config = $config;
        $this->iterator = $iterator;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->log = $log;
    }

    /**
     * {@inheritdoc}
     */
    public function write(Writer $writer, XmlWriter $xml)
    {
        $xml->startElement('items');

        /** @var Store $store */
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->config->isEnabled($store)) {
                $profileKey = 'tweakwise::export::products::' . $store->getCode();
                try {
                    Profiler::start($profileKey);
                    $this->exportStore($writer, $xml, $store);
                } finally {
                    Profiler::stop($profileKey);
                }

                $this->log->debug(sprintf('Export products for store %s', $store->getName()));
            } else {
                $this->log->debug(sprintf('Skip products for store %s (disabled)', $store->getName()));
            }
        }

        $xml->endElement(); // items
        $writer->flush();
        return $this;
    }

    /**
     * @param Writer $writer
     * @param XmlWriter $xml
     * @param Store $store
     * @return $this
     */
    protected function exportStore(Writer $writer, XmlWriter $xml, Store $store)
    {
        $storeId = $store->getId();
        $this->iterator->setStoreId($storeId);

        foreach ($this->iterator as $data) {
            $this->writeProduct($xml, $storeId, $data);
            $writer->flush();
        }
        return $this;
    }


    /**
     * @param XmlWriter $xml
     * @param int $storeId
     * @param array $data
     * @return $this
     */
    protected function writeProduct(XmlWriter $xml, $storeId, array $data)
    {
        $xml->startElement('item');

        // Write product base data
        $tweakwiseId = $this->helper->getTweakwiseId($storeId, $data['entity_id']);
        $xml->writeElement('id', $tweakwiseId);
        $xml->writeElement('price', $data['price']);
        $xml->writeElement('name', $data['name']);
        $xml->writeElement('stock', $data['stock']);

        // Write product categories
        $xml->startElement('categories');
        foreach ($data['categories'] as $categoryId) {
            $categoryTweakwiseId = $this->helper->getTweakwiseId($storeId, $categoryId);
            if ($xml->hasCategoryExport($categoryTweakwiseId)) {
                $xml->writeElement('categoryid', $categoryTweakwiseId);
            } else {
                $this->log->debug(sprintf('Skip product (%s) category (%s) relation', $tweakwiseId, $categoryTweakwiseId));
            }
        }
        $xml->endElement(); // categories

        // Write product attributes
        $xml->startElement('attributes');
        foreach ($data['attributes'] as $attributeKeyValue) {
            $this->writeAttribute($xml, $attributeKeyValue['attribute'], $attributeKeyValue['value']);
        }
        $xml->endElement(); // attributes

        $xml->endElement(); // </item>

        $this->log->debug(sprintf('Export product [%s] %s', $tweakwiseId, $data['name']));
        return $this;
    }


    /**
     * @param XmlWriter $xml
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function writeAttribute(XmlWriter $xml, $name, $value)
    {
        $xml->startElement('attribute');
        $xml->writeAttribute('datatype', is_numeric($value) ? 'numeric' : 'text');
        $xml->writeElement('name', $name);
        $xml->writeElement('value', $value);
        $xml->endElement(); // </attribute>

        return $this;
    }
}