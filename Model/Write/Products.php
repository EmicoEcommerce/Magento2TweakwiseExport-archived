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
use Magento\Framework\Exception\LocalizedException;
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
    public function __construct(
        Config $config,
        Iterator $iterator,
        StoreManager $storeManager,
        Helper $helper,
        Logger $log,
        EavConfig $eavConfig
    ) {
        $this->config = $config;
        $this->iterator = $iterator;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->log = $log;
        $this->eavConfig = $eavConfig;
    }

    /**
     * @param Writer $writer
     * @param XMLWriter $xml
     */
    public function write(Writer $writer, XMLWriter $xml): void
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
    }

    /**
     * @param Writer $writer
     * @param XMLWriter $xml
     * @param Store $store
     * @param int[] $entityIds
     */
    public function exportStore(Writer $writer, XMLWriter $xml, Store $store, array $entityIds = []): void
    {
        $this->iterator->setStore($store);
        // Purge iterator entity ids for each store
        $this->iterator->setEntityIds($entityIds);

        foreach ($this->iterator as $index => $data) {
            $this->writeProduct($xml, $store->getId(), $data);
            // Flush every so often
            if ($index % 100 === 0) {
                $writer->flush();
            }
        }

        // Flush any remaining products
        $writer->flush();
    }


    /**
     * @param XMLWriter $xml
     * @param int $storeId
     * @param array $data
     */
    protected function writeProduct(XMLWriter $xml, $storeId, array $data): void
    {
        $xml->startElement('item');

        // Write product base data
        $tweakwiseId = $this->helper->getTweakwiseId($storeId, $data['entity_id']);
        $xml->writeElement('id', $tweakwiseId);
        $xml->writeElement('name', $this->scalarValue($data['name']));
        $xml->writeElement('price', $this->scalarValue($data['price']));
        $xml->writeElement('stock', $this->scalarValue($data['stock']));

        // Write product categories
        $xml->startElement('categories');
        foreach ($data['categories'] as $categoryId) {
            $categoryTweakwiseId = $this->helper->getTweakwiseId($storeId, $categoryId);
            if ($xml->hasCategoryExport($categoryTweakwiseId)) {
                $xml->writeElement('categoryid', $categoryTweakwiseId);
            } else {
                $this->log->debug(
                    sprintf('Skip product (%s) category (%s) relation', $tweakwiseId, $categoryTweakwiseId)
                );
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
    }


    /**
     * @param XMLWriter $xml
     * @param int $storeId
     * @param string $name
     * @param string|string[]|int|int[]|float|float[] $attributeValue
     */
    public function writeAttribute(
        XMLWriter $xml,
        $storeId,
        $name,
        $attributeValue
    ): void
    {
        $values = $this->normalizeAttributeValue($storeId, $name, $attributeValue);
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
    }

    /**
     * @param int $storeId
     * @param AbstractAttribute $attribute
     * @return string[]
     */
    protected function getAttributeOptionMap($storeId, AbstractAttribute $attribute): array
    {
        $attributeKey = $storeId . '-' . $attribute->getId();
        if (!isset($this->attributeOptionMap[$attributeKey])) {
            $map = [];

            // Set store id to trick in fetching correct options
            $attribute->setData('store_id', $storeId);

            foreach ($attribute->getSource()->getAllOptions() as $option) {
                $map[$option['value']] = (string)$option['label'];
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
            $data = [];
            foreach ($value as $key => $childValue) {
                $data[$key] = $this->scalarValue($childValue);
            }

            return $data;
        }

        if (is_object($value)) {
            if (method_exists($value, 'toString')) {
                $value = $value->toString();
            } else if (method_exists($value, '__toString')) {
                $value = (string)$value;
            } else {
                $value = spl_object_hash($value);
            }
        }

        if (is_numeric($value)) {
            $value = $this->normalizeExponent($value);
        }

        if (!empty($value)) {
            return html_entity_decode($value, ENT_NOQUOTES | ENT_HTML5);
        }

        return '';
    }

    /**
     * @param float|int $value
     * @return float|string
     */
    protected function normalizeExponent($value)
    {
        if (stripos($value, 'E+') !== false) {
            // Assume integer value
            $decimals = 0;
            if (is_float($value)) {
                // Update decimals if not int
                $decimals = 5;
            }

            return number_format($value, $decimals, '.', '');
        }

        return $value;
    }

    /**
     * @param mixed $data
     * @return array
     */
    protected function ensureArray($data): array
    {
        return is_array($data) ? $data : [$data];
    }

    /**
     * @param string[] $data
     * @param string $delimiter
     * @return string[]
     */
    protected function explodeValues(array $data, string $delimiter = ','): array
    {
        $result = [];
        foreach ($data as $value) {
            $result[] = explode($delimiter, $value) ?: [];
        }
        return !empty($result) ? array_merge([], ...$result) : [];
    }

    /**
     * Convert attribute value to array of scalar values.
     *
     * @param int $storeId
     * @param string $attributeCode
     * @param mixed $value
     * @return array
     */
    protected function normalizeAttributeValue(int $storeId, string $attributeCode, $value): array
    {
        $values = $this->ensureArray($value);
        $values = array_map(
            function ($value) {
                return $this->scalarValue($value);
            },
            $values
        );

        try {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, $attributeCode);
        } catch (LocalizedException $e) {
            $this->log->error($e->getMessage());
            return $values;
        }
        // Attribute does not exists so just return value
        if (!$attribute || !$attribute->getId()) {
            return $values;
        }

        // Apparently Magento adds a default source model to the attribute even if it does not use a source
        if (!$attribute->usesSource()) {
            return $values;
        }

        // Explode values if source is used (multi select)
        $values = $this->explodeValues($values);
        try {
            $attributeSource = $attribute->getSource();
        } catch (LocalizedException $e) {
            $this->log->error($e->getMessage());
            return $values;
        }
        if (!$attributeSource instanceof SourceInterface) {
            return $values;
        }

        $result = [];
        /** @var string $attributeValue */
        foreach ($values as $attributeValue) {
            $map = $this->getAttributeOptionMap($storeId, $attribute);
            $result[] = $map[$attributeValue] ?? null;
        }

        return $result;
    }
}
