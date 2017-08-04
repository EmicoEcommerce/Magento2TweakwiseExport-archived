<?php
namespace Helper;

use Codeception\Lib\Interfaces\DependsOnModule;
use Codeception\Module;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class Acceptance extends Module implements DependsOnModule
{
    /**
     * @var Magento2\Bootstrap
     */
    protected $magentoBootstrap;

    /**
     * @var Magento2\Fixtures
     */
    protected $magentoFixtures;

    /**
     * {@inheritdoc}
     */
    public function _depends()
    {
        return [
            Magento2\Bootstrap::class => 'Depends on ' . Magento2\Bootstrap::class,
            Magento2\Fixtures::class => 'Depends on ' . Magento2\Fixtures::class,
        ];
    }

    /**
     * @param Magento2\Bootstrap $magentoBootstrap
     * @param Magento2\Fixtures $magentoFixtures
     */
    public function _inject(Magento2\Bootstrap $magentoBootstrap, Magento2\Fixtures $magentoFixtures)
    {
        $this->magentoBootstrap = $magentoBootstrap;
        $this->magentoFixtures = $magentoFixtures;
    }

    /**
     * {@inheritdoc}
     */
    public function _beforeSuite($settings = [])
    {
        $magentoBootstrap = $this->magentoBootstrap;
        $fixtures = $this->magentoFixtures;

        $magentoBootstrap->initBootstrap();
        $magentoBootstrap->initApplication();
        $magentoBootstrap->initSecureArea();

        $fixtures->loadProductFixtures(['Emico_TweakwiseExport::../tests/_data/acceptance/products.csv'], ['etw-valid', 'etw-empty-attr', 'etw-disabled']);
        $fixtures->writeProductAttribute('etw-empty-attr', 'special_price', null);
        $fixtures->writeProductAttribute('etw-disabled', 'status', Status::STATUS_DISABLED);
    }
}
