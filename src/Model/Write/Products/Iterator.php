<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Config\Source\StockCalculation;
use Emico\TweakwiseExport\Model\Helper;
use Emico\TweakwiseExport\Model\Write\EavIterator;
use Generator;
use Magento\Bundle\Model\Product\Type as BundleType;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\Catalog\Model\Indexer\Category\Product\AbstractAction as CategoryProductAbstractAction;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\DataObject;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use Magento\Framework\Profiler;
use Magento\GroupedProduct\Model\Product\Type\Grouped as GroupType;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link;
use Magento\Store\Model\StoreManager;
use Zend_Db_Expr;
use Zend_Db_Select;

class Iterator extends EavIterator
{
    /**
     * Batch size to fetch extra product data.
     */
    const BATCH_SIZE = 1000;

    /**
     * @var ProductCollectionFactory
     */
    protected $productCollectionFactory;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var Visibility
     */
    protected $visibility;

    /**
     * Product type factory
     *
     * @var ProductType
     */
    protected $productType;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Iterator constructor.
     *
     * @param Helper $helper
     * @param EavConfig $eavConfig
     * @param ProductCollectionFactory $productCollectionFactory
     * @param StoreManager $storeManager
     * @param Visibility $visibility
     * @param Helper $helper
     * @param ProductType $productType
     * @param DbContext $dbContext
     * @param Config $config
     */
    public function __construct(
        Helper $helper,
        EavConfig $eavConfig,
        ProductCollectionFactory $productCollectionFactory,
        StoreManager $storeManager,
        Visibility $visibility,
        ProductType $productType,
        DbContext $dbContext,
        Config $config
    ) {
        parent::__construct($helper, $eavConfig, $dbContext, Product::ENTITY, []);

        $this->productCollectionFactory = $productCollectionFactory;
        $this->storeManager = $storeManager;
        $this->visibility = $visibility;
        $this->productType = $productType;
        $this->config = $config;

        $this->initializeAttributes($this);
    }

    /**
     * @param array $params
     * @return ProductCollection
     */
    protected function createProductCollection(array $params = [])
    {
        return $this->productCollectionFactory->create($params);
    }

    /**
     * Select all attributes who should be exported
     *
     * @param EavIterator $iterator
     * @return $this
     */
    protected function initializeAttributes(EavIterator $iterator)
    {
        // Add default attributes
        $iterator->selectAttribute('name');
        $iterator->selectAttribute('sku');
        $iterator->selectAttribute('url_key');
        $iterator->selectAttribute('status');
        $iterator->selectAttribute('visibility');
        $iterator->selectAttribute('type_id');

        // Add configured attributes
        $type = $this->eavConfig->getEntityType($this->entityCode);

        /** @var Attribute $attribute */
        foreach ($type->getAttributeCollection() as $attribute) {
            if (!$this->helper->shouldExportAttribute($attribute)) {
                continue;
            }

            $iterator->selectAttribute($attribute->getAttributeCode());
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $batch = [];
        foreach (parent::getIterator() as $entity) {
            if ($this->skipEntity($entity)) {
                continue;
            }

            $batch[$entity['entity_id']] = $entity;

            if (count($batch) == self::BATCH_SIZE) {
                // After PHP7+ we can use yield from
                foreach ($this->processBatch($batch) as $processedEntity) {
                    yield $processedEntity;
                }
                $batch = [];
            }
        }

        // After PHP7+ we can use yield from
        foreach ($this->processBatch($batch) as $processedEntity) {
            yield $processedEntity;
        }
    }

    /**
     * @param string $modelEntity
     * @return string
     */
    protected function getTableName($modelEntity)
    {
        return $this->getResources()->getTableName($modelEntity);
    }

    /**
     * @param array $entity
     * @return bool
     */
    protected function skipEntity(array $entity)
    {
        if ($entity['status'] != Status::STATUS_ENABLED) {
            return true;
        }

        if (!in_array($entity['visibility'], $this->visibility->getVisibleInSiteIds())) {
            return true;
        }

        return false;
    }

    /**
     * @param array $entity
     * @param array $stockMap
     * @param int $parentId
     * @return bool
     */
    protected function skipEntityChild(array $entity, array $stockMap, $parentId)
    {
        if ($entity['status'] != Status::STATUS_ENABLED) {
            return true;
        }

        if ($this->config->isOutOfStockChildren()) {
            return false;
        }

        if (!isset($stockMap[$parentId])) {
            return true;
        }

        $parentStockMap = $stockMap[$parentId];
        $entityId = (int) $entity['entity_id'];
        if (!isset($parentStockMap[$entityId])) {
            return true;
        }

        return $parentStockMap[$entityId] <= 0.0001;
    }

    /**
     * @param array $entityIds
     * @return float[][]
     */
    protected function getEntityPriceBatch(array $entityIds)
    {
        if (count($entityIds) == 0) {
            return [];
        }

        $collectionSelect = $this->createProductCollection()
            ->addAttributeToFilter('entity_id', ['in' => $entityIds])
            ->addPriceData(0, $this->storeManager->getStore($this->storeId)->getWebsiteId())
            ->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns([
                'entity_id',
                'price' => new Zend_Db_Expr('IF(price_index.final_price IS NOT NULL AND price_index.final_price != 0, price_index.final_price, price_index.min_price)'),
                'old_price' => 'price_index.price',
                'min_price' => 'price_index.min_price',
                'max_price' => 'price_index.max_price',
            ]);
        $collectionQuery = $collectionSelect->query();

        $result = [];
        while ($row = $collectionQuery->fetch()) {
            $result[(int) $row['entity_id']] = $row;
        }

        return $result;
    }

    /**
     * @param array $entityIds
     * @return int[][]
     */
    protected function getEntityCategoriesBatch(array $entityIds)
    {
        if (count($entityIds) == 0) {
            return [];
        }

        $query = $this->getConnection()
            ->select()
            ->from($this->getTableName(CategoryProductAbstractAction::MAIN_INDEX_TABLE), ['category_id', 'product_id'])
            ->where('store_id = ?', $this->storeId)
            ->where('product_id IN(' . join(',', $entityIds) . ')')
            ->query();

        $result = [];
        while ($row = $query->fetch()) {

            $entityId = (int) $row['product_id'];
            if (!isset($result[$entityId])) {
                $result[$entityId] = [];
            }

            $result[$entityId][] = (int) $row['category_id'];
        }
        return $result;
    }

    /**
     * @param array $parentChildMap
     * @return int[]
     */
    protected function getEntityStockBatch(array $parentChildMap)
    {
        if (count($parentChildMap) == 0) {
            return [];
        }

        $allEntityIds = array_merge(array_keys($parentChildMap), call_user_func_array('array_merge', $parentChildMap));
        $query = $this->getConnection()
            ->select()
            ->from($this->getTableName('cataloginventory_stock_item'), ['product_id', 'qty'])
            ->where('product_id IN(' . join(',', $allEntityIds) . ')')
            ->query();

        $map = [];
        while ($row = $query->fetch()) {
            $productId = (int) $row['product_id'];
            $map[$productId] = (float) $row['qty'];
        }

        $result = [];
        foreach ($parentChildMap as $parentId => $childIds) {
            $parentResult = [];
            if (isset($map[$parentId])) {
                $parentResult[$parentId] = max(0, $map[$parentId]);
            }

            foreach ($childIds as $childId) {
                if (isset($map[$childId])) {
                    $parentResult[$childId] = max(0, $map[$childId]);
                }
            }

            $result[$parentId] = $parentResult;
        }

        return $result;
    }

    /**
     * @param array $parentChildMap
     * @param array $stockMap
     * @return array[]
     */
    protected function getEntityExtraAttributesBatch(array $parentChildMap, array $stockMap)
    {
        if (count($parentChildMap) == 0) {
            return [];
        }

        $map = [];
        foreach ($parentChildMap as $parent => $childIds) {
            foreach ($childIds as $id) {
                $map[$id] = $parent;
            }
        }

        $iterator = new EavIterator($this->helper, $this->eavConfig, $this->dbContext, $this->entityCode, []);
        $iterator->setEntityIds(array_keys($map));
        $this->initializeAttributes($iterator);

        $parentIds = array_keys($parentChildMap);
        $result = array_combine($parentIds, array_fill(0, count($parentIds), []));
        foreach ($iterator as $entity) {
            $parentId = $map[$entity['entity_id']];

            if ($this->skipEntityChild($entity, $stockMap, $parentId)) {
                continue;
            }

            foreach ($entity as $attribute => $value) {
                if ($attribute == 'entity_id') {
                    continue;
                }

                if ($this->skipChildAttribute($attribute)) {
                    continue;
                }

                $result[$parentId][$attribute . $value] = ['attribute' => $attribute, 'value' => $value];
            }
        }

        return $result;
    }

    /**
     * @param array $entities
     * @return Generator
     */
    protected function processBatch(array $entities)
    {
        $entityIds = array_keys($entities);
        try {
            Profiler::start('tweakwise::export::products::getEntityPriceBatch');
            $prices = $this->getEntityPriceBatch($entityIds);
        } finally {
            Profiler::stop('tweakwise::export::products::getEntityPriceBatch');
        }

        try {
            Profiler::start('tweakwise::export::products::getEntityCategoriesBatch');
            $categories = $this->getEntityCategoriesBatch($entityIds);
        } finally {
            Profiler::stop('tweakwise::export::products::getEntityCategoriesBatch');
        }

        try {
            Profiler::start('tweakwise::export::products::getEntityParentChildMap');
            $parentChildMap = $this->getEntityParentChildMap($entities);
        } finally {
            Profiler::stop('tweakwise::export::products::getEntityParentChildMap');
        }

        try {
            Profiler::start('tweakwise::export::products::getEntityStockBatch');
            $stock = $this->getEntityStockBatch($parentChildMap);
        } finally {
            Profiler::stop('tweakwise::export::products::getEntityStockBatch');
        }

        try {
            Profiler::start('tweakwise::export::products::getEntityExtraAttributesBatch');
            $extraAttributes = $this->getEntityExtraAttributesBatch($parentChildMap, $stock);
        } finally {
            Profiler::stop('tweakwise::export::products::getEntityExtraAttributesBatch');
        }

        foreach ($entities as $entityId => $entity) {
            $name = $entity['name'];
            $entityCategories = isset($categories[$entityId]) ? $categories[$entityId] : [];
            $entityStock = isset($stock[$entityId]) ? $stock[$entityId] : [];
            $attributes = isset($extraAttributes[$entityId]) ? $extraAttributes[$entityId] : [];

            // Combine data
            list($entity, $entityPrice) = $this->combinePriceData($prices, $entityId, $entity);
            $attributes = $this->combineExtraAttributes($entity, $attributes);
            $entityStock = $this->combineStock($entityStock);

            // yield return single entity response from batch
            yield [
                'entity_id' => $entityId,
                'name' => $name,
                'price' => $entityPrice,
                'stock' => $entityStock,
                'categories' => $entityCategories,
                'attributes' => $attributes,
            ];
        }
    }

    /**
     * @param int[] $parentIds
     * @return array[]
     */
    protected function getBundleChildIds(array $parentIds)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getTableName('catalog_product_bundle_selection'), ['product_id', 'parent_product_id'])
            ->where('parent_product_id IN (?)', $parentIds);

        $query = $select->query();
        $result = array_combine($parentIds, array_fill(0, count($parentIds), []));
        while ($row = $query->fetch()) {
            $parentId = (int) $row['parent_product_id'];
            $result[$parentId][] = (int) $row['product_id'];
        }
        return $result;
    }

    /**
     * @param int[] $parentIds
     * @param int $typeId
     * @return array[]
     */
    protected function getLinkChildIds(array $parentIds, $typeId)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getTableName('catalog_product_link'), ['linked_product_id', 'product_id'])
            ->where('link_type_id = ?', $typeId)
            ->where('product_id IN (?)', $parentIds);

        $query = $select->query();
        $result = array_combine($parentIds, array_fill(0, count($parentIds), []));
        while ($row = $query->fetch()) {
            $parentId = (int) $row['product_id'];
            $result[$parentId][] = (int) $row['linked_product_id'];
        }
        return $result;
    }

    /**
     * @param int[] $parentIds
     * @return array[]
     */
    protected function getConfigurableChildIds(array $parentIds)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getTableName('catalog_product_super_link'), ['product_id', 'parent_id'])
            ->where('parent_id IN (?)', $parentIds);

        $query = $select->query();
        $result = array_combine($parentIds, array_fill(0, count($parentIds), []));
        while ($row = $query->fetch()) {
            $parentId = (int) $row['parent_id'];
            $result[$parentId][] = (int) $row['product_id'];
        }
        return $result;
    }

    /**
     * @param array $entities
     * @return array
     */
    protected function getEntityParentChildMap(array $entities)
    {
        $groups = [];
        foreach ($entities as $entity) {
            if (!isset($groups[$entity['type_id']])) {
                $groups[$entity['type_id']] = [];
            }

            $groups[$entity['type_id']][$entity['entity_id']] = [];
        }

        $childrenIds = [];
        $types = $this->productType;
        foreach ($groups as $typeId => $group) {
            // Create fake product type to trick type factory to use getTypeId
            /** @var Product $fakeProduct */
            $fakeProduct = new DataObject(['type_id' => $typeId]);
            $type = $types->factory($fakeProduct);

            if (!$type->isComposite($fakeProduct)) {
                continue;
            }

            $parentIds = array_keys($group);
            if ($type instanceof BundleType) {
                $childrenIds = $childrenIds + $this->getBundleChildIds($parentIds);
            } elseif ($type instanceof GroupType) {
                $childrenIds = $childrenIds + $this->getLinkChildIds($parentIds, Link::LINK_TYPE_GROUPED);
            } elseif ($type instanceof ConfigurableType) {
                $childrenIds = $childrenIds + $this->getConfigurableChildIds($parentIds);
            } else {
                foreach ($parentIds as $parentId) {
                    $childrenIds[$parentId] = $type->getChildrenIds($parentId, false);
                }
            }
        }

        return $childrenIds;
    }

    /**
     * @param string $attribute
     * @return bool
     */
    protected function skipChildAttribute($attribute)
    {
        return $this->config->getSkipAttribute($attribute);
    }

    /**
     * @param string $attributeCode
     * @param mixed $value
     * @return mixed
     */
    protected function filterEntityAttributeValue($attributeCode, $value)
    {
        if (!isset($this->attributesByCode[$attributeCode])) {
            return $value;
        }

        $attribute = $this->attributesByCode[$attributeCode];

        // Text values are ok like this
        if (in_array($attribute->getBackendModel(), ['static', 'varchar', 'text', 'datetime'])) {
            return $value;
        }

        // Decimal values can be cast
        if ($attribute->getBackendModel() == 'decimal') {
            // Cleanup empty values
            $value = trim($value);
            if (empty($value)) {
                return null;
            }

            return (float) $value;
        }

        // Convert int backend
        if ($attribute->getBackendModel() == 'int') {
            // If select or multi select skip
            if ($attribute->getFrontendInput() == 'select' || $attribute->getFrontendInput() == 'multiselect') {
                return $value;
            }

            // Cleanup empty values
            $value = trim($value);
            if (empty($value)) {
                return null;
            }

            return (int) $value;

        }

        return $value;
    }

    /**
     * @param array $entity
     * @return array
     */
    protected function filterEntityAttributes(array $entity)
    {
        $result = [];
        foreach ($entity as $attribute) {
            $attribute['value'] = $this->filterEntityAttributeValue($attribute['attribute'], $attribute['value']);
            if (empty($attribute['value'])) {
                continue;
            }

            $result[] = $attribute;
        }

        return $result;
    }

    /**
     * @param $prices
     * @param $entityId
     * @param $entity
     * @return array
     */
    protected function combinePriceData(array $prices, $entityId, array &$entity)
    {
        if (isset($prices[$entityId])) {
            $entity['old_price'] = $prices[$entityId]['old_price'];
            $entity['min_price'] = $prices[$entityId]['min_price'];
            $entity['max_price'] = $prices[$entityId]['max_price'];
            $entityPrice = $prices[$entityId]['price'];
        } else {
            $entityPrice = 0;
        }
        return [$entity, $entityPrice];
    }

    /**
     * @param $entityStock
     * @return float|int|mixed
     */
    protected function combineStock($entityStock)
    {
        switch ($this->config->getStockCalculation()) {
            case StockCalculation::OPTION_MAX:
                return max($entityStock);
            case StockCalculation::OPTION_MIN:
                return min($entityStock);
            case StockCalculation::OPTION_SUM:
            default:
                return array_sum($entityStock);
        }
    }

    /**
     * @param $entity
     * @param $attributes
     * @return array
     */
    protected function combineExtraAttributes($entity, $attributes): array
    {
        foreach ($entity as $attribute => $value) {
            if (in_array($attribute, ['name', 'entity_id'])) {
                continue;
            }

            $attributes[$attribute . $value] = ['attribute' => $attribute, 'value' => $value];
        }
        $attributes = $this->filterEntityAttributes($attributes);
        return $attributes;
    }
}