<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Magento\Catalog\Model\Indexer\Category\Product\AbstractAction;

class CategoryReference extends AbstractDecorator
{
    /**
     * {@inheritdoc}
     */
    public function decorate(Collection $collection)
    {
        $storeId = $collection->getStoreId();
        $select = $this->getConnection()
            ->select()
            ->from($this->getIndexTableName($storeId), ['category_id', 'product_id'])
            ->where('store_id = ?', $collection->getStoreId())
            ->where('product_id IN(' . implode(',', $collection->getIds()) . ')');
        $resultSet = $select->query();

        $result = [];
        while ($row = $resultSet->fetch()) {
            $entityId = (int) $row['product_id'];
            $entity = $collection->get($entityId);
            $entity->addCategoryId((int) $row['category_id']);
        }

        return $result;
    }

    /**
     * @param int $storeId
     * @return string
     */
    protected function getIndexTableName(int $storeId)
    {
        $baseTableName = AbstractAction::MAIN_INDEX_TABLE;
        $categoryProductIndexTable = sprintf(
            '%s_store%s',
            $baseTableName,
            $storeId
        );
        $categoryProductIndexTable = $this->getTableName($categoryProductIndexTable);

        if ($this->getConnection()->isTableExists($categoryProductIndexTable)) {
            return $categoryProductIndexTable;
        }

        return $this->getTableName($baseTableName);
    }
}