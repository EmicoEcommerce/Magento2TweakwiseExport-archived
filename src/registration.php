<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

use Magento\Framework\Component\ComponentRegistrar;

if (class_exists(ComponentRegistrar::class, false)) {
    ComponentRegistrar::register(
        ComponentRegistrar::MODULE,
        'Emico_TweakwiseExport',
        __DIR__
    );
}