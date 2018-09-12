<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;

abstract class AbstractDecorator implements DecoratorInterface
{
    /**
     * @var DbContext
     */
    private $dbContext;

    /**
     * AbstractDecorator constructor.
     *
     * @param DbContext $dbContext
     */
    public function __construct(DbContext $dbContext)
    {
        $this->dbContext = $dbContext;
    }

    /**
     * @param string $modelEntity
     * @return string
     */
    protected function getTableName($modelEntity): string
    {
        return $this->getResources()->getTableName($modelEntity);
    }

    /**
     * @return AdapterInterface
     */
    protected function getConnection()
    {
        return $this->getResources()->getConnection();
    }

    /**
     * @return ResourceConnection
     */
    protected function getResources()
    {
        return $this->dbContext->getResources();
    }
}