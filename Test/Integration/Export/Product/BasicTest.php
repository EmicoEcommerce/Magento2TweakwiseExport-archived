<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Test\Integration\Export\Product;

use Emico\TweakwiseExport\Test\Integration\ExportTest;

/**
 * @magentoDbIsolation enabled
 */
class BasicTest extends ExportTest
{
    /**
     * Test if empty export does not throw error
     */
    public function testEmptyExport()
    {
        $file = __DIR__ . '/../../../../../tests/data/integration/export/product/basic/empty.xml';
        $this->assertStringEqualsFile($file, (string) $this->exportFeed());
    }

    /**
     * Test export with one product and check on product data
     */
    public function testOneProduct()
    {
        $product = $this->productData->create();
        $feed = $this->exportFeed();

        $feedProduct = $feed->getProduct($product->getId());
        $feedProduct->assertSku($product->getSku());
        $feedProduct->assertName($product->getName());
        $feedProduct->assertPrice($product->getPrice());
        $feedProduct->assertAttributes([
            'sku' => $product->getSku(),
            'type_id' => 'simple',
            'tax_class_id' => 'Taxable Goods',
            'old_price' => $product->getPrice(),
            'min_price' => $product->getPrice(),
            'max_price' => $product->getPrice(),
        ]);
        $feedProduct->assertCategories([100012]);
    }
}
