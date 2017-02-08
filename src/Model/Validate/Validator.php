<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Model\Validate;

use Emico\TweakwiseExport\Exception\ValidationException;
use Magento\Framework\Profiler;

class Validator
{
    /**
     * @param string $file
     * @throws ValidationException
     */
    public function validate($file)
    {
        try {
            Profiler::start('tweakwise::export::validate');

        } finally {
            Profiler::stop('tweakwise::export::validate');
        }
    }
}
