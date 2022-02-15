<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Model\Write;

use Emico\TweakwiseExport\Exception\InvalidArgumentException;
use Emico\TweakwiseExport\Model\Helper;
use IteratorAggregate;
use Magento\Framework\Event\Manager;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Statement\Pdo\Mysql as MysqlStatement;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use Magento\Framework\Profiler;
use Magento\Store\Model\Store;
use Zend_Db_Expr;
use Zend_Db_Select;

class EavIterator implements IteratorAggregate
{
    /**
     * @var int
     */
    protected $batchSize;

    /**
     * @var Manager
     */
    protected $eventManager;

    /**
     * @var string
     */
    protected $entityCode;

    /**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @var AbstractAttribute[]
     */
    protected $attributes = [];

    /**
     * @var AbstractAttribute[]
     */
    protected $attributesByCode = [];

    /**
     * @var Store
     */
    protected $store;

    /**
     * @var int[]
     */
    protected $entityIds = [];

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var DbContext
     */
    protected $dbContext;

    /**
     * @var \ArrayIterator[]
     */
    protected $entitySet;

    /**
     * @var array
     */
    protected $eavSelectOrder = [
        'entity_id',
        'store_id',
    ];

    /**
     * @var array
     */
    protected $entityBatchOrder = [];

    /**
     * EavIterator constructor.
     *
     * @param int $batchSize
     * @param Helper $helper
     * @param EavConfig $eavConfig
     * @param DbContext $dbContext
     * @param string $entityCode
     * @param string[] $attributes
     */
    public function __construct(
        Helper $helper,
        EavConfig $eavConfig,
        DbContext $dbContext,
        Manager $eventManager,
        string $entityCode,
        array $attributes,
        int $batchSize = 5000
    ) {
        $this->eavConfig = $eavConfig;
        $this->entityCode = $entityCode;
        $this->helper = $helper;
        $this->dbContext = $dbContext;
        $this->attributes = [];
        $this->batchSize = $batchSize;
        $this->eventManager = $eventManager;
        foreach ($attributes as $attribute) {
            $this->selectAttribute($attribute);
        }
    }

    /**
     * @param string $attributeCode
     */
    public function selectAttribute(string $attributeCode): void
    {
        $attribute = $this->eavConfig->getAttribute($this->entityCode, $attributeCode);
        /** @noinspection NullPointerExceptionInspection */
        $attributeKey = $attribute->getId() ?: $attributeCode;

        $this->attributes[$attributeKey] = $attribute;
        $this->attributesByCode[$attributeCode] = $attribute;
    }

    /**
     * @param string $attributeCode
     */
    public function removeAttribute(string $attributeCode): void
    {
        $attribute = $this->eavConfig->getAttribute($this->entityCode, $attributeCode);
        /** @noinspection NullPointerExceptionInspection */
        $attributeKey = $attribute->getId() ?: $attributeCode;

        if (!isset($this->attributes[$attributeKey])) {
            throw new InvalidArgumentException(sprintf('Attribute %s not found', $attributeCode));
        }

        unset($this->attributes[$attributeKey], $this->attributesByCode[$attributeCode]);
    }

    /**
     * @param Store $store
     */
    public function setStore(Store $store): void
    {
        $this->store = $store;
    }

    /**
     * @param int[] $entityIds
     */
    public function setEntityIds(array $entityIds): void
    {
        $this->entityIds = $entityIds;
    }

    /**
     * @return int[]
     */
    public function getEntityIds(): array
    {
        return $this->entityIds;
    }

    /**
     * @param MysqlStatement $stmt
     * @return \Generator
     * @throws \Zend_Db_Statement_Exception
     */
    protected function loopUnionRows(MysqlStatement $stmt)
    {
        $entity = ['entity_id' => null];
        while ($row = $stmt->fetch()) {
            $attributeId = $row['attribute_id'];
            $value = $row['value'];

            if (!isset($this->attributes[$attributeId])) {
                continue;
            }

            $attribute = $this->attributes[$attributeId];
            $attributeCode = $attribute->getAttributeCode();
            $rowEntityId = (int)$row['entity_id'];

            if ($entity['entity_id'] !== $rowEntityId) {
                // If current loop entity is new yield return this entity
                if ($entity['entity_id']) {
                    yield $entity;
                }

                $entity = [
                    'entity_id' => (int)$row['entity_id'],
                    $attributeCode => $value,
                ];
            } else {
                // Add row to current looping entity
                if (isset($entity[$attributeCode])) {
                    // Only override if store specific
                    if ($row['store_id'] > 0) {
                        $entity[$attributeCode] = $value;
                    }
                } else {
                    $entity[$attributeCode] = $value;
                }
            }
        }

        if ($entity['entity_id']) {
            yield $entity;
        }
    }

    /**
     * @return Zend_Db_Select
     */
    protected function createSelect(): Zend_Db_Select
    {
        $select = $this->getConnection()
            ->select()
            ->union($this->getAttributeSelects());

        $this->addEavSelectOrder($select);

        return $select;
    }

    /**
     * @Return \AppendIterator
     */
    public function getIterator()
    {
        while ($entityIds = $this->getEntityBatch()) {
            try {
                Profiler::start('eav-iterator::' . $this->entityCode);
                $this->setEntityIds($entityIds);
                $select = $this->createSelect();

                Profiler::start('query');
                try {
                    /** @var MysqlStatement $stmt */
                    $stmt = $select->query();
                } finally {
                    Profiler::stop('query');
                }

                Profiler::start('loop');
                try {
                    $this->eventManager->dispatch('tweakwise_iterator_processbatch');
                    // Loop over all rows and combine them to one array for entity
                    foreach ($this->loopUnionRows($stmt) as $result) {
                        yield $result;
                    }
                } finally {
                    Profiler::stop('loop');
                }
            } finally {
                Profiler::stop('eav-iterator::' . $this->entityCode);
            }
        }
    }

    /**
     * @return int[]|null
     */
    protected function getEntityBatch(): ?array
    {
        $storeId = $this->store->getId();
        if (!isset($this->entitySet[$storeId])) {
            $select = $this->getConnection()->select();
            $select->from($this->getEntityType()->getEntityTable());
            $select->reset('columns')->columns('entity_id');
            $this->addEntityBatchOrder($select);

            if ($this->getEntityIds()) {
                $select->where('entity_id IN (?)', $this->getEntityIds());
            }

            $result = $select->query()->fetchAll();
            $result = array_column($result, 'entity_id');
            $this->entitySet[$storeId] = new \ArrayIterator(array_chunk($result, $this->batchSize));
        }

        $return = $this->entitySet[$storeId]->current();
        $this->entitySet[$storeId]->next();
        return $return;
    }

    /**
     * Add order fields
     * @param Zend_Db_Select $select
     */
    protected function addEavSelectOrder(\Zend_Db_Select $select): void
    {
        foreach ($this->eavSelectOrder as $order) {
            $select->order($order);
        }
    }

    /**
     * @param Zend_Db_Select $select
     */
    protected function addEntityBatchOrder(\Zend_Db_Select $select): void
    {
        foreach ($this->entityBatchOrder as $order) {
            $select->order($order);
        }
    }

    /**
     * @return AbstractAttribute[]
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return AdapterInterface
     */
    protected function getConnection(): AdapterInterface
    {
        return $this->getResources()->getConnection();
    }

    /**
     * @return ResourceConnection
     */
    protected function getResources(): ResourceConnection
    {
        return $this->dbContext->getResources();
    }

    /**
     * @return Type
     */
    protected function getEntityType(): Type
    {
        return $this->eavConfig->getEntityType($this->entityCode);
    }

    /**
     * @param AbstractAttribute[] $attributes
     * @return Select[]
     */
    protected function getStaticAttributeSelect(array $attributes): array
    {
        $connection = $this->getConnection();

        $selects = [];
        foreach ($attributes as $attributeKey => $attribute) {
            $attributeExpression = new Zend_Db_Expr($connection->quote($attributeKey));
            $select = $connection->select()
                ->from(
                    $attribute->getBackendTable(),
                    [
                        'entity_id',
                        'store_id' => new Zend_Db_Expr('0'),
                        'attribute_id' => $attributeExpression,
                        'value' => $attribute->getAttributeCode()
                    ]
                );
            if ($this->entityIds) {
                $select->where("{$attribute->getBackendTable()}.entity_id IN (?)", $this->entityIds);
            }

            $selects[] = $select;
        }

        return $selects;
    }

    /**
     * @param string $table
     * @param AbstractAttribute[] $attributes
     * @return Select
     */
    protected function getAttributeSelectCommunity(string $table, array $attributes): Select
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($table, ['entity_id', 'store_id', 'attribute_id', 'value'])
            ->where('attribute_id IN (?)', array_keys($attributes));

        $storeId = $this->store->getId();
        if ($storeId) {
            $select->where('store_id = 0 OR store_id = ?',$storeId);
        } else {
            $select->where('store_id = 0');
        }

        if ($this->entityIds) {
            $select->where("{$table}.entity_id IN (?)", $this->entityIds);
        }

        return $select;
    }

    /**
     * @param string $table
     * @param AbstractAttribute[] $attributes
     * @return Select
     */
    protected function getAttributeSelectEnterprise(string $table, array $attributes): Select
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['attribute_table' => $table], [])
            ->join(['main_table' => $this->getEntityType()->getEntityTable()], 'attribute_table.row_id = main_table.row_id', [])
            ->columns([
                'entity_id' => 'main_table.entity_id',
                'store_id' => 'attribute_table.store_id',
                'attribute_id' => 'attribute_table.attribute_id',
                'value' => 'attribute_table.value'
            ])
            ->where('attribute_id IN (?)', array_keys($attributes));

        $storeId = $this->store->getId();
        if ($storeId) {
            $select->where('store_id = 0 OR store_id = ?', $storeId);
        } else {
            $select->where('store_id = 0');
        }

        if ($this->entityIds) {
            $select->where('entity_id IN (?)', $this->entityIds);
        }

        return $select;
    }

    /**
     * @return AbstractAttribute[][]
     */
    protected function getAttributeGroups(): array
    {
        $attributeGroups = [];
        foreach ($this->attributes as $attributeId => $attribute) {
            $table = $attribute->getBackendTable();
            if ($attribute->isStatic()) {
                $table = '_static';
            }

            if (!isset($attributeGroups[$table])) {
                $attributeGroups[$table] = [];
            }
            $attributeGroups[$table][$attributeId] = $attribute;
        }
        return $attributeGroups;
    }

    /**
     * @param string $group
     * @param AbstractAttribute[] $attributes
     * @return Select
     */
    protected function createEavAttributeGroupSelect(string $group, array $attributes): Select
    {
        if ($this->helper->isEnterprise()) {
            return $this->getAttributeSelectEnterprise($group, $attributes);
        }

        return $this->getAttributeSelectCommunity($group, $attributes);
    }

    /**
     * @return Select[]
     */
    protected function getAttributeSelects(): array
    {
        $selects = [];
        $attributeGroups = $this->getAttributeGroups();

        foreach ($attributeGroups as $group => $attributes) {
            if ($group === '_static') {
                foreach ($this->getStaticAttributeSelect($attributes) as $select) {
                    $selects[] = $select;
                }
            } else {
                $selects[] = $this->createEavAttributeGroupSelect($group, $attributes);
            }
        }

        return $selects;
    }
}
