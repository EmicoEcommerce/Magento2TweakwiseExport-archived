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
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\Framework\Exception\NoSuchEntityException;

class Fixtures extends Module
{
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
        $this->getBootstrap()->emulateAreaCode('setup', function() use ($fixtures, $insertedSkus) {
            $this->ensureDeleteProduct($insertedSkus);

            /** @var Product $productSampleData */
            $productSampleData = $this->getBootstrap()->getObject(Product::class);
            $productSampleData->install($fixtures, []);
        });
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
            try {
                $product = $repository->get($sku);
            } catch (NoSuchEntityException $e) {
                continue;
            }

            if ($product->getId()) {
                $repository->delete($product);
            }
        }
    }

    /**
     * @param string $sku
     * @param string $attribute
     * @param mixed $value
     */
    public function writeProductAttribute($sku, $attribute, $value)
    {
        $bootstrap = $this->getBootstrap();
        // Only with a raw insert like this we where able to insert an empty value in the special_price table for issue #6
        /** @var ProductRepository $repository */
        $repository = $bootstrap->getObject(ProductRepository::class);
        /** @var ProductResource $resource */
        $resource = $bootstrap->getObject(ProductResource::class);

        $attribute = $resource->getAttribute($attribute);

        $table = $attribute->getBackend()->getTable();
        $product = $repository->get($sku);
        $entityIdField = $attribute->getBackend()->getEntityIdField();

        $data = [
            $entityIdField => $product->getId(),
            'attribute_id' => $attribute->getId(),
            'value' => $value,
        ];
        $resource->getConnection()->insertOnDuplicate($table, $data, ['value']);
    }
}
