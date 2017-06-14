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

class EmptyAttributeCest
{
    /**
     * Product SKU of empty attribute
     */
    const PRODUCT_SKU = 'emico-tweakwise-export-sprc';

    /**
     * @param FunctionalTester $i
     */
    public function _before(FunctionalTester $i)
    {
        $i->initArea(Area::AREA_CRONTAB);
        $i->loadProductFixtures(
            ['Emico_TweakwiseExport::../tests/fixtures/product/empty-special-price.csv'],
            [self::PRODUCT_SKU]
        );


        // Only with a raw insert like this we where able to insert an empty value in the special_price table for issue #6
        /** @var ProductRepository $repository */
        $repository = $i->getObject(ProductRepository::class);
        /** @var Product $resource */
        $resource = $i->getObject(Product::class);

        $attribute = $resource->getAttribute('special_price');

        $table = $attribute->getBackend()->getTable();
        $product = $repository->get(self::PRODUCT_SKU);
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
    public function tryToTest(FunctionalTester $i)
    {
        /** @var Writer $writer */
        $writer = $i->getObject(Writer::class);
        $resource = fopen('php://temp', 'w+');
        $writer->write($resource);
        rewind($resource);

        $content = stream_get_contents($resource);
        file_put_contents('/Volumes/WWW/tweakwise2-ce.dev/var/feeds/tweakwise.xml', $content);
        $xml = simplexml_load_string($content);
        foreach ($xml->xpath('//item/attributes/attribute') as $attributeElement) {
            $name = (string) $attributeElement->name;
            if ($name != 'special_price') {
                continue;
            }

            $value = (string) $attributeElement->value;
            $value = trim($value);
            $i->assertNotEmpty($value);
        }
    }
}
