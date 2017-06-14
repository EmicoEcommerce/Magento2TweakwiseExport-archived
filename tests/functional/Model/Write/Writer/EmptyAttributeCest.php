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
    }

    /**
     * @param FunctionalTester $i
     */
    public function tryToTest(FunctionalTester $i)
    {

    }
}
