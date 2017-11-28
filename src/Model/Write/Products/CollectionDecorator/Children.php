<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Write\EavIteratorFactory;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Emico\TweakwiseExport\Model\Write\Products\CollectionFactory;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntityChild;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntityChildFactory;
use Emico\TweakwiseExport\Model\Write\Products\IteratorInitializer;
use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\DataObject;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use Magento\GroupedProduct\Model\Product\Type\Grouped;
use Magento\GroupedProduct\Model\ResourceModel\Product\Link;

class Children extends AbstractDecorator
{
    /**
     * @var ProductType
     */
    private $productType;

    /**
     * @var EavIteratorFactory
     */
    private $eavIteratorFactory;

    /**
     * @var ExportEntityFactory
     */
    private $entityChildFactory;

    /**
     * @var Collection
     */
    protected $childEntities;

    /**
     * @var IteratorInitializer
     */
    private $iteratorInitializer;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var Config
     */
    private $config;

    /**
     * ChildId constructor.
     *
     * @param DbContext $dbContext
     * @param ProductType $productType
     * @param EavIteratorFactory $eavIteratorFactory
     * @param IteratorInitializer $iteratorInitializer
     * @param ExportEntityChildFactory $entityChildFactory
     * @param CollectionFactory $collectionFactory
     * @param Config $config
     */
    public function __construct(
        DbContext $dbContext,
        ProductType $productType,
        EavIteratorFactory $eavIteratorFactory,
        IteratorInitializer $iteratorInitializer,
        ExportEntityChildFactory $entityChildFactory,
        CollectionFactory $collectionFactory,
        Config $config
    )
    {
        parent::__construct($dbContext);
        $this->productType = $productType;
        $this->eavIteratorFactory = $eavIteratorFactory;
        $this->entityChildFactory = $entityChildFactory;
        $this->iteratorInitializer = $iteratorInitializer;
        $this->collectionFactory = $collectionFactory;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(Collection $collection)
    {
        $this->childEntities = $this->collectionFactory->create(['storeId' => $collection->getStoreId()]);
        $this->createChildEntities($collection);
        $this->loadChildAttributes();
    }

    /**
     * @param Collection $collection
     */
    private function createChildEntities(Collection $collection)
    {
        foreach ($this->getGroupedEntities($collection) as $typeId => $group) {
            // Create fake product type to trick type factory to use getTypeId
            /** @var Product $fakeProduct */
            $fakeProduct = new DataObject(['type_id' => $typeId]);
            $type = $this->productType->factory($fakeProduct);
            $isComposite = $type->isComposite($fakeProduct);
            foreach ($group as $entity) {
                $entity->setIsComposite($isComposite);
            }

            $parentIds = array_keys($group);
            if ($type instanceof Bundle) {
                $this->addBundleChildren($collection, $parentIds);
            } elseif ($type instanceof Grouped) {
                $this->addLinkChildren($collection, $parentIds, Link::LINK_TYPE_GROUPED);
            } elseif ($type instanceof Configurable) {
                $this->addConfigurableChildren($collection, $parentIds);
            } else {
                foreach ($parentIds as $parentId) {
                    foreach ($type->getChildrenIds($parentId, false) as $childId) {
                        $this->addChild($collection, (int) $parentId, (int) $childId);
                    }
                }
            }
        }
    }

    /**
     * @param Collection $collection
     * @return ExportEntity[][]
     */
    private function getGroupedEntities(Collection $collection): array
    {
        $groups = [];
        foreach ($collection as $entity) {
            $typeId = $entity->getAttribute('type_id', false);
            if (!isset($groups[$typeId])) {
                $groups[$typeId] = [];
            }

            $groups[$typeId][$entity->getId()] = $entity;
        }
        return $groups;
    }

    /**
     * @param Collection $collection
     * @param int[] $parentIds
     */
    private function addBundleChildren(Collection $collection, array $parentIds)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getTableName('catalog_product_bundle_selection'), ['product_id', 'parent_product_id'])
            ->where('parent_product_id IN (?)', $parentIds);

        $query = $select->query();
        while ($row = $query->fetch()) {
            $this->addChild($collection, (int) $row['parent_product_id'], (int) $row['product_id']);
        }
    }

    /**
     * @param Collection $collection
     * @param int[] $parentIds
     * @param int $typeId
     */
    private function addLinkChildren(Collection $collection, array $parentIds, $typeId)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getTableName('catalog_product_link'), ['linked_product_id', 'product_id'])
            ->where('link_type_id = ?', $typeId)
            ->where('product_id IN (?)', $parentIds);

        $query = $select->query();
        while ($row = $query->fetch()) {
            $this->addChild($collection, (int) $row['product_id'], (int) $row['linked_product_id']);
        }
    }

    /**
     * @param Collection $collection
     * @param int[] $parentIds
     */
    private function addConfigurableChildren(Collection $collection, array $parentIds)
    {
        $connection = $this->getConnection();

        $select = $connection->select()
            ->from($this->getTableName('catalog_product_super_link'), ['product_id', 'parent_id'])
            ->where('parent_id IN (?)', $parentIds);

        $query = $select->query();
        while ($row = $query->fetch()) {
            $this->addChild($collection, (int) $row['parent_id'], (int) $row['product_id']);
        }
    }

    /**
     * @param Collection $collection
     * @param int $parentId
     * @param int $childId
     */
    private function addChild(Collection $collection, int $parentId, int $childId)
    {
        if (!$this->childEntities->has($childId)) {
            $child = $this->entityChildFactory->create(['data' => ['entity_id' => $childId]]);
            $this->childEntities->add($child);
        } else {
            $child = $this->childEntities->get($childId);
        }

        $collection->get($parentId)->addChild($child);
    }

    /**
     * Load child attribute data
     */
    private function loadChildAttributes()
    {
        if ($this->childEntities->count() === 0) {
            return;
        }

        $iterator = $this->eavIteratorFactory->create(['entityCode' => Product::ENTITY, 'attributes' => []]);
        $iterator->setEntityIds($this->childEntities->getIds());
        $this->iteratorInitializer->initializeAttributes($iterator);

        foreach ($iterator->getAttributes() as $attribute) {
            if ($this->config->getSkipChildAttribute($attribute->getAttributeCode())) {
                $iterator->removeAttribute($attribute->getAttributeCode());
            }
        }

        foreach ($iterator as $childData) {
            $childId = (int) $childData['entity_id'];
            $childEntity = $this->childEntities->get($childId);
            $childEntity->setFromArray($childData);
        }
    }
}