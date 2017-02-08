<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Model\Write;

use Emico\TweakwiseExport\Exception\WriteException;
use Magento\Framework\Profiler;

class Writer
{
    /**
     * @param resource $resource
     * @throws WriteException
     */
    public function write($resource)
    {
        try {
            Profiler::start('tweakwise::export::write');
            fwrite($resource, 'test');
        } finally {
            Profiler::stop('tweakwise::export::write');
        }
    }
}
