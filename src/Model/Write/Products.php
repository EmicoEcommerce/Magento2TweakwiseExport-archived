<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Model\Write;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Helper;
use Emico\TweakwiseExport\Model\Logger;
use Emico\TweakwiseExport\Model\Write\Products\Iterator;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\Source\SourceInterface;
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
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @var array
     */
    protected $attributeOptionMap = [];

    /**
     * Products constructor.
     *
     * @param Config $config
     * @param Iterator $iterator
     * @param StoreManager $storeManager
     * @param Helper $helper
     * @param Logger $log
     * @param EavConfig $eavConfig
     */
    public function __construct(Config $config, Iterator $iterator, StoreManager $storeManager, Helper $helper, Logger $log, EavConfig $eavConfig)
    {
        $this->config = $config;
        $this->iterator = $iterator;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->log = $log;
        $this->eavConfig = $eavConfig;
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
                $profileKey = 'products::' . $store->getCode();
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
        $xml->writeElement('name', $this->scalarValue($data['name']));
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
            $this->writeAttribute($xml, $storeId, $attributeKeyValue['attribute'], $attributeKeyValue['value']);
        }
        $xml->endElement(); // attributes

        $xml->endElement(); // </item>

        $this->log->debug(sprintf('Export product [%s] %s', $tweakwiseId, $data['name']));
        return $this;
    }


    /**
     * @param XmlWriter $xml
     * @param int $storeId
     * @param string $name
     * @param string|string[]|int|int[]|float|float[] $value
     * @return $this
     */
    public function writeAttribute(XmlWriter $xml, $storeId, $name, $value)
    {
        $values = $this->normalizeAttributeValue($storeId, $name, $value);
        $values = array_unique($values);

        foreach ($values as $value) {
            if (empty($value)) {
                continue;
            }

            $xml->startElement('attribute');
            $xml->writeAttribute('datatype', is_numeric($value) ? 'numeric' : 'text');
            $xml->writeElement('name', $name);
            $xml->writeElement('value', $value);
            $xml->endElement(); // </attribute>
        }

        return $this;
    }

    /**
     * @param int $storeId
     * @param AbstractAttribute $attribute
     * @return string[]
     */
    protected function getAttributeOptionMap($storeId, AbstractAttribute $attribute)
    {
        $attributeKey = $storeId . '-' . $attribute->getId();
        if (!isset($this->attributeOptionMap[$attributeKey])) {
            $map = [];

            // Set store id to trick in fetching correct options
            $attribute->setData('store_id', $storeId);

            foreach ($attribute->getSource()->getAllOptions() as $option) {
                $map[$option['value']] = (string) $option['label'];
            }

            $this->attributeOptionMap[$attributeKey] = $map;
        }

        return $this->attributeOptionMap[$attributeKey];
    }

    /**
     * Get scalar value from object, array or scalar value
     *
     * @param mixed $value
     *
     * @return string|array
     */
    protected function scalarValue($value)
    {
        if (is_array($value)) {
            $data = array();
            foreach ($value as $key => $childValue) {
                $data[$key] = $this->scalarValue($childValue);
            }

            return $data;
        } else if (is_object($value)) {
            if (method_exists($value, 'toString')) {
                $value = $value->toString();
            } else if (method_exists($value, '__toString')) {
                $value = (string) $value;
            } else {
                $value = spl_object_hash($value);
            }
        }

        return html_entity_decode($value, ENT_NOQUOTES | ENT_HTML5);
    }

    /**
     * @param mixed $data
     * @return array
     */
    protected function ensureArray($data)
    {
        return is_array($data) ? $data : [$data];
    }

    /**
     * @param array $data
     * @param string $delimiter
     * @return array
     */
    protected function explodeValues(array $data, $delimiter = ',')
    {
        $result = [];
        foreach ($data as $value) {
            $result = array_merge($result, explode($delimiter, $value));
        }
        return $result;
    }

    /**
     * Convert attribute value to array of scalar values.
     *
     * @param int $storeId
     * @param string $attributeCode
     * @param mixed $value
     * @return array
     */
    protected function normalizeAttributeValue($storeId, $attributeCode, $value)
    {
        $values = $this->ensureArray($value);
        $values = array_map(function($value) { return $this->scalarValue($value); }, $values);

        $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
        // Attribute does not exists so just return value
        if (!$attribute->getId()) {
            return $values;
        }

        // Apparently Magento adds a default source model to the attribute even if it does not use a source
        if (!$attribute->usesSource()) {
            return $values;
        }

        // Explode values if source is used (multi select)
        $values = $this->explodeValues($values);
        if (!$attribute->getSource() instanceof SourceInterface) {
            return $values;
        }

        $result = [];
        foreach ($values as $attributeValue) {
            $map = $this->getAttributeOptionMap($storeId, $attribute);
            $result[] = $map[$attributeValue] ?? null;
        }

        return $result;
    }
}