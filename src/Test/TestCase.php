<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Test;

if (class_exists('PHPUnit\Framework\TestCase')) {
    abstract class BaseTestCase extends \PHPUnit\Framework\TestCase
    { }
} else {
    abstract class BaseTestCase extends \PHPUnit_Framework_TestCase
    { }
}

abstract class TestCase extends BaseTestCase
{

}