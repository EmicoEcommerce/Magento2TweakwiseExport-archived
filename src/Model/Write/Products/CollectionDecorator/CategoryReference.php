<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Emico\TweakwiseExport\Model\DbResourceHelper;
use Magento\Catalog\Model\Indexer\Category\Product\AbstractAction;

class CategoryReference implements DecoratorInterface
{
    /**
     * @var DbResourceHelper
     */
    protected $dbResource;

    /**
     * CategoryReference constructor.
     * @param DbResourceHelper $resourceHelper
     */
    public function __construct(DbResourceHelper $resourceHelper)
    {
        $this->dbResource = $resourceHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(Collection $collection)
    {
        $storeId = $collection->getStoreId();
        $select = $this->dbResource->getConnection()
            ->select()
            ->from($this->getIndexTableName($storeId), ['category_id', 'product_id'])
            ->where('store_id = ?', $collection->getStoreId())
            ->where('product_id IN(' . implode(',', $collection->getIds()) . ')');
        $resultSet = $select->query();

        while ($row = $resultSet->fetch()) {
            $entityId = (int) $row['product_id'];
            $entity = $collection->get($entityId);
            $entity->addCategoryId((int) $row['category_id']);
        }
    }

    /**
     * @param int $storeId
     * @return string
     */
    protected function getIndexTableName(int $storeId): string
    {
        $baseTableName = AbstractAction::MAIN_INDEX_TABLE;
        $categoryProductIndexTable = sprintf(
            '%s_store%s',
            $baseTableName,
            $storeId
        );
        $categoryProductIndexTable = $this->dbResource->getTableName($categoryProductIndexTable);

        if ($this->dbResource->getConnection()->isTableExists($categoryProductIndexTable)) {
            return $categoryProductIndexTable;
        }

        return $this->dbResource->getTableName($baseTableName);
    }
}
