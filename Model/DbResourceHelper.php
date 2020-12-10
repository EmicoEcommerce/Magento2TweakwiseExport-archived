<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;

class DbResourceHelper
{
    /**
     * @var DbContext
     */
    protected $dbContext;

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
    public function getTableName($modelEntity): string
    {
        return $this->getResources()->getTableName($modelEntity);
    }

    /**
     * @return AdapterInterface
     */
    public function getConnection(): AdapterInterface
    {
        return $this->getResources()->getConnection();
    }

    /**
     * @return ResourceConnection
     */
    public function getResources(): ResourceConnection
    {
        return $this->dbContext->getResources();
    }
}
