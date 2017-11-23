<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Test\Integration\Export\Product;

use Emico\TweakwiseExport\Test\Integration\ExportTest;

class BasicTest extends ExportTest
{
    /**
     * Test if empty export does not throw error
     */
    public function testEmptyExport()
    {
        $this->assertFeedResult('integration/export/product/basic/empty.xml');
    }

    /**
     * Test export with one product and check on product data
     */
    public function testOneProduct()
    {
        $product = $this->productData->create();
        $feed = $this->exportFeed();

        $this->assertProductData(
            $feed,
            $product->getSku(),
            $product->getName(),
            $product->getPrice(),
            [
                'sku' => $product->getSku(),
                'type_id' => 'simple',
                'status' => 'Enabled',
                'visibility' => '4',
                'tax_class_id' => 'Taxable Goods',
                'price' => $product->getPrice(),
                'old_price' => $product->getPrice(),
                'min_price' => $product->getPrice(),
                'max_price' => $product->getPrice(),
            ],
            [
                100012
            ]
        );
    }
}