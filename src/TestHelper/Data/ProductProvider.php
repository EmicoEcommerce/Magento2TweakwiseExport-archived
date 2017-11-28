<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\TestHelper\Data;

use Emico\TweakwiseExport\TestHelper\Data\Product\AttributeProvider;
use Faker\Factory;
use Faker\Generator;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\Data\ProductInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Product as ProductResource;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Framework\App\Area;
use Magento\Store\Model\App\Emulation;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class ProductProvider
{
    /**
     * Product default stock qty
     */
    const DEFAULT_STOCK_QTY = 100;

    /**
     * @var Generator
     */
    private $faker;

    /**
     * @var ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var ProductInterfaceFactory
     */
    private $productFactory;

    /**
     * @var StockRegistryInterface
     */
    private $stockRegistry;

    /**
     * @var CategoryLinkManagementInterface
     */
    private $categoryLinkManagement;

    /**
     * @var EntityHydrator
     */
    private $hydrator;

    /**
     * @var CategoryProvider
     */
    private $categoryProvider;
    /**
     * @var AttributeProvider
     */
    private $attributeProvider;

    /**
     * @var Emulation
     */
    private $emulation;

    /**
     * @var ProductResource
     */
    private $productResource;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * CategoryDataProvider constructor.
     *
     * @param ProductRepositoryInterface $productRepository
     * @param ProductInterfaceFactory $productFactory
     * @param StockRegistryInterface $stockRegistry
     * @param CategoryLinkManagementInterface $categoryLinkManagement
     * @param EntityHydrator $hydrator
     * @param CategoryProvider $categoryProvider
     * @param AttributeProvider $attributeProvider
     * @param Emulation $emulation
     * @param ProductResource $productResource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ProductRepositoryInterface $productRepository,
        ProductInterfaceFactory $productFactory,
        StockRegistryInterface $stockRegistry,
        CategoryLinkManagementInterface $categoryLinkManagement,
        EntityHydrator $hydrator,
        CategoryProvider $categoryProvider,
        AttributeProvider $attributeProvider,
        Emulation $emulation,
        ProductResource $productResource,
        StoreManagerInterface $storeManager
    )
    {
        $this->faker = Factory::create();
        $this->productRepository = $productRepository;
        $this->productFactory = $productFactory;
        $this->stockRegistry = $stockRegistry;
        $this->categoryLinkManagement = $categoryLinkManagement;
        $this->hydrator = $hydrator;
        $this->categoryProvider = $categoryProvider;
        $this->attributeProvider = $attributeProvider;
        $this->emulation = $emulation;
        $this->productResource = $productResource;
        $this->storeManager = $storeManager;
    }

    /**
     * @param array $data
     * @return ProductInterface
     */
    public function create(array $data = []): ProductInterface
    {
        $product = $this->productFactory->create();

        // Set product defaults
        $product->setSku('test-' . $this->faker->uuid);
        $product->setName($this->faker->name);
        $product->setTypeId(Product\Type::TYPE_SIMPLE);
        $product->setVisibility(Product\Visibility::VISIBILITY_BOTH);
        $product->setPrice($this->faker->randomNumber(2));
        $product->setAttributeSetId($this->attributeProvider->getSetId());
        $product->setStatus(Product\Attribute\Source\Status::STATUS_ENABLED);

        // Overwrite with provided data
        $this->hydrator->hydrate($data, $product);

        // Save product
        $this->emulation->startEnvironmentEmulation(Store::DEFAULT_STORE_ID, Area::AREA_ADMINHTML);
        try {
            $product = $this->productRepository->save($product);
        } finally {
            $this->emulation->stopEnvironmentEmulation();
        }

        // Ensure product qty
        $data['qty'] = $data['qty'] ?? self::DEFAULT_STOCK_QTY;
        $this->updateStockItem($product, $data);

        // Assign product to categories
        $categoryIds = $data['category_ids'] ?? [$this->categoryProvider->getDefaultRootId()];
        $this->categoryLinkManagement->assignProductToCategories($product->getSku(), $categoryIds);

        return $product;
    }

    /**
     * @param ProductInterface $product
     * @param string $attribute
     * @param $value
     * @param string|null $store
     */
    public function saveAttribute(ProductInterface $product, string $attribute, $value, string $store = null)
    {
        $product = clone $product;

        $updateData = [$attribute => $value];
        if ($store) {
            $updateData['store_id'] = $this->storeManager->getStore($store)->getId();
        }

        $this->hydrator->hydrate($updateData, $product);

        /** @var $product Product */
        $this->productResource->saveAttribute($product, $attribute);
    }

    /**
     * @param ProductInterface $product
     * @param array $data
     * @return StockItemInterface
     */
    public function updateStockItem(ProductInterface $product, array $data): StockItemInterface
    {
        $stockItem = $this->stockRegistry->getStockItemBySku($product->getSku());
        $this->hydrator->hydrate($data, $stockItem);
        $this->stockRegistry->updateStockItemBySku($product->getSku(), $stockItem);
        return $stockItem;
    }
}