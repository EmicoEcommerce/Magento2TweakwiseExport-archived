<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Helper;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Magento\Catalog\Model\Indexer\Category\Product\AbstractAction;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;

class CategoryReference extends AbstractDecorator
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * CategoryReference constructor.
     * @param DbContext $dbContext
     * @param Helper $helper
     */
    public function __construct(DbContext $dbContext, Helper $helper)
    {
        parent::__construct($dbContext);
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(Collection $collection)
    {
        if ($this->helper->isEnterprise()) {
            $this->addCategoryReferenceEnterprise($collection);
        } else {
            $this->addCategoryReferenceCommunity($collection);
        }
    }

    /**
     * @param Collection $collection
     */
    private function addCategoryReferenceEnterprise(Collection $collection)
    {
        $productEntityRowIdMap = $this->getEntityIdRowIdMap($collection->getIds());
        $entityIds = array_keys($productEntityRowIdMap);

        $storeId = $collection->getStoreId();
        $select = $this->getConnection()
            ->select()
            ->from($this->getIndexTableName($storeId), ['category_id', 'product_id'])
            ->where('store_id = ?', $storeId)
            ->where('product_id IN (?)', $entityIds);

        $result = $select->query()->fetchAll();
        $categoryEntityIds = array_column($result, 'category_id');
        $categoryEntityIds = array_flip(array_flip($categoryEntityIds));

        $categoryEntityRowIdMap = $this->getCategoryEntityRowIdMap($categoryEntityIds);
        foreach ($result as $row) {
            $entityId = (int) $row['product_id'];
            $entity = $collection->get($productEntityRowIdMap[$entityId]);
            $entity->addCategoryId((int) $categoryEntityRowIdMap[$row['category_id']]);
        }
    }

    /**
     * @param Collection $collection
     */
    private function addCategoryReferenceCommunity(Collection $collection)
    {
        $storeId = $collection->getStoreId();
        $select = $this->getConnection()
            ->select()
            ->from($this->getIndexTableName($storeId), ['category_id', 'product_id'])
            ->where('store_id = ?', $storeId)
            ->where('product_id IN (?)', $collection->getIds());

        $query = $select->query();
        while ($row = $query->fetch()) {
            $entityId = (int) $row['product_id'];
            $entity = $collection->get($entityId);
            $entity->addCategoryId((int) $row['category_id']);
        }
    }

    /**
     * @param $storeId
     * @return string
     */
    private function getIndexTableName($storeId)
    {
        if ($this->helper->isEnterprise()) {
            return $this->getTableName(sprintf('%s_store%s', AbstractAction::MAIN_INDEX_TABLE, $storeId));
        }

        return $this->getTableName(AbstractAction::MAIN_INDEX_TABLE);
    }

    /**
     * @param array $categoryEntityIds
     */
    private function getCategoryEntityRowIdMap(array $categoryEntityIds)
    {
        $select = $this->getConnection()->select();
        $select->from($this->getTableName('catalog_category_entity'))
            ->reset('columns')
            ->columns(['entity_id', 'row_id'])
            ->where('entity_id IN (?)', $categoryEntityIds);

        $result = $select->query()->fetchAll();
        $rowIds = array_column($result, 'row_id');
        $entityIds = array_column($result, 'entity_id');
        return array_combine($entityIds, $rowIds);
    }
}