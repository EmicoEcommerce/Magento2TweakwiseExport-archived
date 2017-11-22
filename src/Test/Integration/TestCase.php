<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Test\Integration;

use Magento\Framework\App\Area;
use Magento\Store\Model\ScopeInterface;
use Magento\TestFramework\Helper\Bootstrap;
use Magento\Framework\App\Config\MutableScopeConfigInterface;

abstract class TestCase extends \Emico\TweakwiseExport\Test\TestCase
{
    /**
     * Set admin area
     */
    protected function setUp()
    {
        Bootstrap::getInstance()->loadArea(Area::AREA_ADMINHTML);
    }

    /**
     * @param string $type
     * @param array $arguments
     * @return mixed
     */
    protected function getObject(string $type, array $arguments = [])
    {
        return Bootstrap::getObjectManager()->get($type, $arguments);
    }

    /**
     * @param string $type
     * @param array $arguments
     * @return mixed
     */
    protected function createObject(string $type, array $arguments = [])
    {
        return Bootstrap::getObjectManager()->create($type, $arguments);
    }

    /**
     * @param string $path
     * @param mixed $value
     * @return $this
     */
    protected function setConfig(string $path, $value)
    {
        /** @var MutableScopeConfigInterface $config */
        $config = $this->getObject(MutableScopeConfigInterface::class);
        $config->setValue($path, $value, ScopeInterface::SCOPE_STORE, 'default');
        return $this;
    }
}