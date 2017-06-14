<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Helper\Magento2;

use Codeception\Module;
use Codeception\TestInterface;
use Magento\Catalog\Model\ProductRepository;
use Magento\CatalogSampleData\Model\Product;

class Fixtures extends Module
{
    /**
     * @var array
     */
    protected $insertedProducts = [];

    /**
     * @param TestInterface $test
     */
    public function _after(TestInterface $test)
    {
        $this->ensureDeleteProduct($this->insertedProducts);
        $this->insertedProducts = [];
    }

    /**
     * @return Bootstrap
     */
    protected function getBootstrap()
    {
        /** @var Bootstrap $bootstrap */
        $bootstrap = $this->getModule(Bootstrap::class);
        return $bootstrap;
    }

    /**
     * @param array $fixtures
     * @param array $insertedSkus
     */
    public function loadProductFixtures(array $fixtures, array $insertedSkus)
    {
        $this->ensureDeleteProduct($insertedSkus);
        $this->getBootstrap()->emulateAreaCode('setup', function() use ($fixtures) {
            /** @var Product $productSampleData */
            $productSampleData = $this->getBootstrap()->getObject(Product::class);
            $productSampleData->install($fixtures, []);
        });
        $this->insertedProducts = array_merge($this->insertedProducts, $insertedSkus);
    }

    /**
     * Ensure test product is deleted
     *
     * @param string[] $skus
     */
    public function ensureDeleteProduct(array $skus)
    {
        /** @var ProductRepository $repository */
        $repository = $this->getBootstrap()->getObject(ProductRepository::class);

        foreach ($skus as $sku) {
            $product = $repository->get($sku);
            if ($product->getId()) {
                $repository->delete($product);
            }
        }
    }
}
