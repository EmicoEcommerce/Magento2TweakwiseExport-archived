<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Helper;

use Codeception\Module;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem as Flysystem;

/**
 * Class Filesystem
 *
 * @package Helper
 */
class Filesystem extends Module
{
    /**
     * @return Flysystem
     */
    public function data()
    {
        $adapter = new Local(__DIR__ . '/../../_data');
        return new Flysystem($adapter);
    }
}