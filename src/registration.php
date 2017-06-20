<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

use Magento\Framework\Component\ComponentRegistrar;

// Used class as string due to the magento compiler issue #9
if (class_exists('Magento\Framework\Component\ComponentRegistrar\ComponentRegistrar', false)) {
    ComponentRegistrar::register(
        ComponentRegistrar::MODULE,
        'Emico_TweakwiseExport',
        __DIR__
    );
}