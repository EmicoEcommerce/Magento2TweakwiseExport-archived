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
use Magento\Catalog\Model\ProductRepository;
use Magento\Catalog\Model\ResourceModel\Product;
use Magento\Framework\App\Area;
use SimpleXMLElement;

class WriterCest
{
    /**
     * Product SKU's
     */
    const SKU_EMPTY_ATTRIBUTE = 'etw-empty-attr';
    const SKU_DISABLED = 'etw-disabled';

    /**
     * @var SimpleXMLElement
     */
    protected $exportXml;

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
                    self::SKU_EMPTY_ATTRIBUTE,
                    self::SKU_DISABLED,
                ]
            );
            $this->updateEmptyAttributeValue($i);

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
     * @param FunctionalTester $i
     */
    protected function updateEmptyAttributeValue(FunctionalTester $i)
    {
        // Only with a raw insert like this we where able to insert an empty value in the special_price table for issue #6
        /** @var ProductRepository $repository */
        $repository = $i->getObject(ProductRepository::class);
        /** @var Product $resource */
        $resource = $i->getObject(Product::class);

        $attribute = $resource->getAttribute('special_price');

        $table = $attribute->getBackend()->getTable();
        $product = $repository->get(self::SKU_EMPTY_ATTRIBUTE);
        $entityIdField = $attribute->getBackend()->getEntityIdField();

        $data = [
            $entityIdField => $product->getId(),
            'attribute_id' => $attribute->getId(),
            'value' => null,
        ];
        $resource->getConnection()->insertOnDuplicate($table, $data, ['value']);
    }

    /**
     * @param FunctionalTester $i
     */
    public function testEmptyAttribute(FunctionalTester $i)
    {
        foreach ($this->exportXml->xpath('//item/attributes/attribute') as $attributeElement) {
            $name = (string) $attributeElement->name;
            if ($name != 'special_price') {
                continue;
            }

            $value = (string) $attributeElement->value;
            $value = trim($value);
            $i->assertNotEmpty($value);
        }
    }

    /**
     * @param FunctionalTester $i
     */
    public function testNotExportingDisabledProduct(FunctionalTester $i)
    {
        foreach ($this->exportXml->xpath('//item/attributes/attribute') as $attributeElement) {
            $name = (string) $attributeElement->name;
            if ($name != 'sku') {
                continue;
            }

            $sku = (string) $attributeElement->value;
            $i->assertNotEquals(self::SKU_DISABLED, $sku);
        }
    }
}
