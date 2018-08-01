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
        $tableName = sprintf('%s_store%d', AbstractAction::MAIN_INDEX_TABLE, $collection->getStoreId());

        $query = $this->getConnection()
            ->select()
            ->from($this->getTableName($tableName), ['category_id', 'product_id'])
            ->where('product_id IN(' . implode(',', $collection->getIds()) . ')')
            ->query();

        $result = [];
        while ($row = $query->fetch()) {
            $entityId = (int) $row['product_id'];
            $entity = $collection->get($entityId);
            $entity->addCategoryId((int) $row['category_id']);
        }

        return $result;
    }
}