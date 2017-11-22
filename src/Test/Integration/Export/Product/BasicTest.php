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
    public function testEmptyExport()
    {
        $this->assertFeedResult('integration/export/product/basic/empty.xml');
    }

    public function oneProductTest()
    {
        $this->assertFeedResult('integration/export/product/basic/one-product.xml');
    }
}