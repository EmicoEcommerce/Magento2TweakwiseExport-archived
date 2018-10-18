<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Magento\Catalog\Model\Indexer\Category\Product\AbstractAction;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use Magento\Catalog\Model\Indexer\Category\Product\TableMaintainer;

class CategoryReference extends AbstractDecorator
{
    /**
     * @var TableMaintainer
     */
    protected $tableMaintainer;

    /**
     * Constructor.
     *
     * @param DbContext       $dbContext
     * @param TableMaintainer $tableMaintainer
     */
    public function __construct(
        DbContext $dbContext,
        TableMaintainer $tableMaintainer
    ) {
        parent::__construct($dbContext);
        $this->tableMaintainer = $tableMaintainer;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(Collection $collection)
    {
        $query = $this->getConnection()
            ->select()
            ->from($this->getTableName($this->tableMaintainer->getMainTable($collection->getStoreId())), ['category_id', 'product_id'])
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