<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Helper;
use Emico\TweakwiseExport\Model\Write\EavIteratorFactory;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Emico\TweakwiseExport\Model\Write\Products\CollectionFactory;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntityChildFactory;
use Emico\TweakwiseExport\Model\Write\Products\IteratorInitializer;
use Magento\Bundle\Model\Product\Type as Bundle;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable;
use Magento\Framework\App\ProductMetadataInterface;
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
     * @var ExportEntityChildFactory
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
     * @var Helper
     */
    private $helper;

    /**
     * ChildId constructor.
     *
     * @param DbContext $dbContext
     * @param ProductType $productType
     * @param EavIteratorFactory $eavIteratorFactory
     * @param IteratorInitializer $iteratorInitializer
     * @param ExportEntityChildFactory $entityChildFactory
     * @param CollectionFactory $collectionFactory
     * @param Helper $helper
     */
    public function __construct(
        DbContext $dbContext,
        ProductType $productType,
        EavIteratorFactory $eavIteratorFactory,
        IteratorInitializer $iteratorInitializer,
        ExportEntityChildFactory $entityChildFactory,
        CollectionFactory $collectionFactory,
        Helper $helper
    )
    {
        parent::__construct($dbContext);
        $this->productType = $productType;
        $this->eavIteratorFactory = $eavIteratorFactory;
        $this->entityChildFactory = $entityChildFactory;
        $this->iteratorInitializer = $iteratorInitializer;
        $this->collectionFactory = $collectionFactory;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(Collection $collection)
    {
        $this->childEntities = $this->collectionFactory->create(['storeId' => $collection->getStoreId()]);
        $this->createChildEntities($collection);
        $this->loadChildAttributes($collection->getStoreId());
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
                $entity->setChildren([]);
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
        $select = $connection->select();

        if ($this->helper->isEnterprise()) {
            $select
                ->from(['product_table' => $this->getTableName('catalog_product_entity')])
                ->reset('columns')
                ->columns(['parent_product_id' => 'product_table.entity_id'])
                ->join(
                    ['bundle_selection' => $this->getTableName('catalog_product_bundle_selection')],
                    'bundle_selection.parent_product_id = product_table.row_id',
                    ['product_id']
                )
                ->where('product_table.row_id IN (?)', $parentIds);
        } else {
            $select
                ->from(['bundle_selection' => $this->getTableName('catalog_product_bundle_selection')])
                ->columns(['product_id', 'parent_product_id'])
                ->where('parent_product_id IN (?)', $parentIds);
        }

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
        $select = $connection->select();

        if ($this->helper->isEnterprise()) {
            $select
                ->from(['product_table' => $this->getTableName('catalog_product_entity')])
                ->reset('columns')
                ->columns(['parent_id' => 'product_table.entity_id'])
                ->join(
                    ['link_table' => $this->getTableName('catalog_product_link')],
                    'link_table.product_id = product_table.row_id',
                    ['linked_product_id']
                )
                ->where('product_table.entity_id IN (?)', $parentIds)
                ->where('link_table.link_type_id = ?', $typeId);
        } else {
            $select
                ->from(['link_table' => $this->getTableName('catalog_product_link')])
                ->where('link_type_id = ?', $typeId)
                ->where('product_id IN (?)', $parentIds);
        }


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
        $select = $connection->select();


        if ($this->helper->isEnterprise()) {
            $select
                ->from(['product_table' => $this->getTableName('catalog_product_entity')])
                ->reset('columns')
                ->columns(['parent_id' => 'product_table.entity_id'])
                ->join(
                    ['link_table' => $this->getTableName('catalog_product_super_link')],
                    'link_table.parent_id = product_table.row_id',
                    ['product_id']
                )
                ->where('product_table.entity_id IN (?)', $parentIds);
        } else {
            $select
                ->from(['link_table' => $this->getTableName('catalog_product_super_link')])
                ->columns(['product_id', 'parent_id'])
                ->where('parent_id IN (?)', $parentIds);
        }


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
            $child = $this->entityChildFactory->create(['storeId' => $collection->getStoreId(), 'data' => ['entity_id' => $childId]]);
            $this->childEntities->add($child);
        } else {
            $child = $this->childEntities->get($childId);
        }

        $collection->get($parentId)->addChild($child);
    }

    /**
     * Load child attribute data
     */
    private function loadChildAttributes(int $storeId)
    {
        if ($this->childEntities->count() === 0) {
            return;
        }

        $iterator = $this->eavIteratorFactory->create(['entityCode' => Product::ENTITY, 'attributes' => []]);
        $iterator->setEntityIds($this->childEntities->getIds());
        $iterator->setStoreId($storeId);
        $this->iteratorInitializer->initializeAttributes($iterator);

        foreach ($iterator as $childData) {
            $childId = (int) $childData['entity_id'];
            $childEntity = $this->childEntities->get($childId);
            $childEntity->setFromArray($childData);
        }
    }
}