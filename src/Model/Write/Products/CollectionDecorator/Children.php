<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntityFactory;
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
    private $eavIterator;

    /**
     * @var ExportEntityFactory
     */
    private $entityFactory;

    /**
     * @var ExportEntity[]
     */
    protected $childEntities;

    /**
     * ChildId constructor.
     *
     * @param DbContext $dbContext
     * @param ProductType $productType
     * @param EavIteratorFactory $eavIterator
     * @param ExportEntityFactory $entityFactory
     */
    public function __construct(
        DbContext $dbContext,
        ProductType $productType,
        EavIteratorFactory $eavIterator,
        ExportEntityFactory $entityFactory
    )
    {
        parent::__construct($dbContext);
        $this->productType = $productType;
        $this->eavIterator = $eavIterator;
        $this->entityFactory = $entityFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(Collection $collection)
    {
        $this->childEntities = [];
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
            $typeId = $entity->getAttribute('type_id');
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
        if (!isset($this->childEntities[$childId])) {
            $child = $this->entityFactory->create(['entity_id' => $childId]);
            $this->childEntities[$childId] = $child;
        } else {
            $child = $this->childEntities[$childId];
        }

        $collection->get($parentId)->addChild($child);
    }
}