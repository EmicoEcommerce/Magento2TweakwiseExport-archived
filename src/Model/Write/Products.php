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
use Emico\TweakwiseExport\Model\Write\Products\Iterator;
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
     * Products constructor.
     *
     * @param Config $config
     * @param Iterator $iterator
     * @param StoreManager $storeManager
     * @param Helper $helper
     */
    public function __construct(Config $config, Iterator $iterator, StoreManager $storeManager, Helper $helper)
    {
        $this->config = $config;
        $this->iterator = $iterator;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function write(Writer $writer, XmlWriter $xml)
    {
        $xml->startElement('products');

        foreach ($this->storeManager->getStores() as $store) {
            if ($this->config->isEnabled($store)) {
                $this->exportStore($writer, $xml, $store);
            }
        }

        $xml->endElement(); // products
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
        $xml->startElement('product');

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
            $xml->writeElement('categoryid', $categoryTweakwiseId);
        }
        $xml->endElement(); // categories

        // Write product attributes
        $xml->startElement('attributes');
        foreach ($data['attributes'] as $attributeKeyValue) {
            $this->writeAttribute($xml, $attributeKeyValue['attribute'], $attributeKeyValue['value']);
        }
        $xml->endElement(); // attributes

        $xml->endElement(); // </product>
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