<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;

class StockCalculation implements ArrayInterface
{
    /**
     * Modes
     */
    const OPTION_SUM = 'sum';
    const OPTION_MAX = 'max';
    const OPTION_MIN = 'min';

    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::OPTION_SUM, 'label' => __('Sum')],
            ['value' => self::OPTION_MAX, 'label' => __('Maximum')],
            ['value' => self::OPTION_MIN, 'label' => __('Minimum')],
        ];
    }
}
