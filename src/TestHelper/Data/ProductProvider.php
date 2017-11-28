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
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Action as ProductAction;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockRegistryInterface;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
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
     * @var ProductAction
     */
    private $productAction;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var EavConfig
     */
    private $eavConfig;

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
     * @param ProductAction $productAction
     * @param StoreManagerInterface $storeManager
     * @param EavConfig $eavConfig
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
        ProductAction $productAction,
        StoreManagerInterface $storeManager,
        EavConfig $eavConfig
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
        $this->productAction = $productAction;
        $this->storeManager = $storeManager;
        $this->eavConfig = $eavConfig;
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

        $attributeObject = $this->eavConfig->getAttribute(Product::ENTITY, $attribute);
        $attributeObject->setData(Attribute::KEY_IS_GLOBAL, ScopedAttributeInterface::SCOPE_STORE);

        $updateData = [$attribute => $value];
        if ($store) {
            $storeId = $this->storeManager->getStore($store)->getId();
        } else {
            $storeId = Store::DEFAULT_STORE_ID;
        }

        $this->hydrator->hydrate($updateData, $product);

        /** @var $product Product */
        $this->productAction->updateAttributes([$product->getId()], $updateData, $storeId);
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