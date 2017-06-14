<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Model\Write\Writer;

use Emico\TweakwiseExport\Model\Write\Writer;
use FunctionalTester;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Framework\App\Area;
use SimpleXMLElement;

class WriterCest
{
    /**
     * Product SKU's
     */
    const SKU_VALID = 'etw-valid';
    const SKU_EMPTY_ATTRIBUTE = 'etw-empty-attr';
    const SKU_DISABLED = 'etw-disabled';

    /**
     * @var SimpleXMLElement
     */
    protected $exportXml;

    /**
     * @var array[]
     */
    protected $exportItems = null;

    /**
     * @param FunctionalTester $i
     */
    public function _before(FunctionalTester $i)
    {
        $i->initArea(Area::AREA_CRONTAB);

        if (!$this->exportXml) {
            $i->loadProductFixtures(
                ['Emico_TweakwiseExport::../tests/fixtures/writer.csv'],
                [
                    self::SKU_VALID,
                    self::SKU_EMPTY_ATTRIBUTE,
                    self::SKU_DISABLED,
                ]
            );
            $i->writeProductAttribute(self::SKU_EMPTY_ATTRIBUTE, 'special_price', null);
            $i->writeProductAttribute(self::SKU_DISABLED, 'status', Status::STATUS_DISABLED);

            // Run Export
            /** @var Writer $writer */
            $writer = $i->getObject(Writer::class);
            $resource = fopen('php://temp', 'w+');
            $writer->write($resource);
            rewind($resource);

            $this->exportXml = simplexml_load_string(stream_get_contents($resource));
        }
    }

    /**
     * @param string $sku
     * @return null|array
     */
    protected function getProductItem($sku)
    {
        if ($this->exportItems === null) {
            $this->exportItems = [];
            foreach ($this->exportXml->xpath('//item') as $element) {
                $data = [
                    'xml' => $element,
                    'id' => (string) $element->id,
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

                $key = isset($data['attributes']['sku']) ? $data['attributes']['sku'] : $data['id'];
                $this->exportItems[$key] = $data;
            }
        }

        return isset($this->exportItems[$sku]) ? $this->exportItems[$sku] : null;
    }

    /**
     * @param string $sku
     * @param string $attribute
     * @return null|string
     */
    protected function getProductAttribute($sku, $attribute)
    {
        $item = $this->getProductItem($sku);
        if (!$item) {
            return null;
        }

        if (!isset($item['attributes'][$attribute])) {
            return null;
        }

        return $item['attributes'][$attribute];
    }

    /**
     * @param FunctionalTester $i
     */
    public function testEmptyAttribute(FunctionalTester $i)
    {
        $value = $this->getProductAttribute(self::SKU_EMPTY_ATTRIBUTE, 'special_price');
        $i->assertNull($value);
        $value = $this->getProductAttribute(self::SKU_VALID, 'special_price');
        $i->assertNotNull($value);
    }

    /**
     * @param FunctionalTester $i
     */
    public function testNotExportingDisabledProduct(FunctionalTester $i)
    {
        $value = $this->getProductItem(self::SKU_DISABLED);
        $i->assertNull($value);
        $value = $this->getProductItem(self::SKU_VALID);
        $i->assertNotNull($value);
    }

    /**
     * @param FunctionalTester $i
     */
    public function testExportMultipleCategories(FunctionalTester $i)
    {
        $value = $this->getProductItem(self::SKU_VALID);
        $i->assertNotNull($value);
        $i->assertGreaterOrEquals(2, $value['categories']);
    }
}
