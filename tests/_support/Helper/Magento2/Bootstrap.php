<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Helper\Magento2;

use Codeception\Exception\Skip;
use Codeception\Module;
use Codeception\TestInterface;
use Magento\Framework\App\Area;
use Magento\Framework\App\Bootstrap as AppBootstrap;
use Magento\Framework\App\Cron;
use Magento\Framework\App\State as AppState;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;

class Bootstrap extends Module
{
    /**
     * @var AppBootstrap
     */
    protected $bootstrap;

    /**
     * @var bool
     */
    protected $moduleReRegistered = false;

    /**
     * {@inheritdoc}
     */
    public function _before(TestInterface $test)
    {
        $this->initBootstrap();
        $this->initApplication();
        $this->initSecureArea();
    }

    /**
     * @return ObjectManagerInterface
     */
    public function getObjectManager()
    {
        return $this->bootstrap->getObjectManager();
    }

    /**
     * @param string $class
     * @return object
     */
    public function getObject($class)
    {
        return $this->getObjectManager()->get($class);
    }

    /**
     * Emulate callback inside some area code
     *
     * @param string $areaCode
     * @param callable $callback
     * @param array $params
     * @return mixed
     */
    public function emulateAreaCode($areaCode, $callback, $params = [])
    {
        /** @var AppState $appState */
        $appState = $this->getObject(AppState::class);
        return $appState->emulateAreaCode($areaCode, $callback, $params);
    }

    /**
     * @param string $areaCode
     */
    public function initArea($areaCode)
    {
        /** @var AppState $appState */
        $appState = $this->getObject(AppState::class);
        $appState->setAreaCode($areaCode);

        $configLoader = $this->getObject('Magento\Framework\ObjectManager\ConfigLoaderInterface');
        $this->getObjectManager()->configure($configLoader->load(Area::AREA_CRONTAB));
    }

    /**
     * Include application bootstrap
     */
    protected function initBootstrap()
    {
        $path = getcwd();
        do {
            $bootstrapFile = $path . DIRECTORY_SEPARATOR . 'app/bootstrap.php';
            if (file_exists($bootstrapFile)) {
                break;
            }

            $path = dirname($path);
        } while ($path != '/');

        if (!file_exists($bootstrapFile)) {
            throw new Skip('Could not find magento root folder (searching for app/bootstrap.php in ' . getcwd());
        }

        require $bootstrapFile;
    }

    /**
     * Init Magento application
     */
    protected function initApplication()
    {
        if (!$this->moduleReRegistered) {
            // Register module again because now we have a class required.
            require __DIR__ . '/../../../../src/registration.php';
            $this->moduleReRegistered = true;
        }

        $this->bootstrap = AppBootstrap::create(BP, $_SERVER);
        $this->bootstrap->createApplication(Cron::class);
    }

    /**
     * Init secure areay registry key
     */
    protected function initSecureArea()
    {
        /** @var Registry $registry */
        $registry = $this->getObject(Registry::class);
        $registry->unregister('isSecureArea');
        $registry->register('isSecureArea', true);
    }
}
