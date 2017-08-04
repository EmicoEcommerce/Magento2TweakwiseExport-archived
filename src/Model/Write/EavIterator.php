<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Model\Write;

use IteratorAggregate;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Type;
use Magento\Framework\App\ProductMetadata as CommunityProductMetadata;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Statement\Pdo\Mysql as MysqlStatement;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use Magento\Framework\Profiler;
use Magento\Setup\Module\I18n\Dictionary\Generator;
use Zend_Db_Expr;
use Zend_Db_Select;

class EavIterator implements IteratorAggregate
{
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
     * @var int
     */
    protected $storeId = 0;

    /**
     * @var int[]
     */
    protected $entityIds;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var DbContext
     */
    protected $dbContext;

    /**
     * @var array
     */
    protected $eavSelectOrder = [
        'entity_id',
        'store_id',
    ];

    /**
     * EavIterator constructor.
     *
     * @param ProductMetadataInterface $productMetadata
     * @param EavConfig $eavConfig
     * @param DbContext $dbContext
     * @param string $entityCode
     * @param string[] $attributes
     */
    public function __construct(ProductMetadataInterface $productMetadata, EavConfig $eavConfig, DbContext $dbContext, $entityCode, array $attributes)
    {
        $this->eavConfig = $eavConfig;
        $this->entityCode = $entityCode;
        $this->productMetadata = $productMetadata;
        $this->dbContext = $dbContext;
        $this->attributes = [];
        foreach ($attributes as $attribute) {
            $this->selectAttribute($attribute);
        }
    }

    /**
     * @param string $attributeCode
     * @return $this
     */
    public function selectAttribute($attributeCode)
    {
        $attribute = $this->eavConfig->getAttribute($this->entityCode, $attributeCode);
        $attributeKey = $attribute->getId() ? $attribute->getId() : $attributeCode;
        $this->attributes[$attributeKey] = $attribute;
        $this->attributesByCode[$attributeCode] = $attribute;
        return $this;
    }

    /**
     * @param int $storeId
     * @return $this
     */
    public function setStoreId($storeId)
    {
        $this->storeId = (int) $storeId;
        return $this;
    }

    /**
     * @param int[] $entityIds
     * @return $this
     */
    public function setEntityIds(array $entityIds)
    {
        $this->entityIds = $entityIds;
        return $this;
    }

    /**
     * @return int[]
     */
    public function getEntityIds()
    {
        return $this->entityIds;
    }

    /**
     * @param MysqlStatement $stmt
     * @return array[]|Generator
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

            if ($entity['entity_id'] != $row['entity_id']) {
                // If current loop entity is new yield return this entity
                if ($entity['entity_id']) {
                    yield $entity;
                }

                $entity = [
                    'entity_id' => (int) $row['entity_id'],
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
    protected function createSelect()
    {
        $select = $this->getConnection()
            ->select()
            ->union($this->getAttributeSelects());

        foreach ($this->eavSelectOrder as $field) {
            $select->order($field);
        }

        return $select;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        try {
            Profiler::start('eav-iterator::' . $this->entityCode);

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
                // Loop over all rows and combine them to one array for entity
                return $this->loopUnionRows($stmt);
            } finally {
                Profiler::stop('loop');
            }
        } finally {
            Profiler::stop('eav-iterator::' . $this->entityCode);
        }
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

    /**
     * @return Type
     */
    protected function getEntityType()
    {
        return $this->eavConfig->getEntityType($this->entityCode);
    }

    /**
     * @param AbstractAttribute[] $attributes
     * @return Select[]
     */
    protected function getStaticAttributeSelect(array $attributes)
    {
        $connection = $this->getConnection();

        $selects = [];
        foreach ($attributes as $attributeKey => $attribute) {
            $attributeExpression = new Zend_Db_Expr($connection->quote($attributeKey));
            $selects[] = $connection->select()
                ->from(
                    $attribute->getBackendTable(),
                    [
                        'entity_id',
                        'store_id' => new Zend_Db_Expr('0'),
                        'attribute_id' => $attributeExpression,
                        'value' => $attribute->getAttributeCode()
                    ]
                );
        }

        return $selects;
    }

    /**
     * @param string $table
     * @param AbstractAttribute[] $attributes
     * @return Select
     */
    protected function getAttributeSelectCommunity($table, array $attributes)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($table, ['entity_id', 'store_id', 'attribute_id', 'value'])
            ->where('attribute_id IN (?)', array_keys($attributes));

        if ($this->storeId) {
            $select->where('store_id = 0 OR store_id = ?', $this->storeId);
        } else {
            $select->where('store_id = 0');
        }

        return $select;
    }

    /**
     * @param string $table
     * @param AbstractAttribute[] $attributes
     * @return Select
     */
    protected function getAttributeSelectEnterprise($table, array $attributes)
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

        if ($this->storeId) {
            $select->where('store_id = 0 OR store_id = ?', $this->storeId);
        } else {
            $select->where('store_id = 0');
        }

        return $select;
    }

    /**
     * @return AbstractAttribute[][]
     */
    protected function getAttributeGroups()
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
    protected function createEavAttributeGroupSelect($group, array $attributes)
    {
        if ($this->productMetadata->getEdition() == CommunityProductMetadata::EDITION_NAME) {
            return $this->getAttributeSelectCommunity($group, $attributes);
        } else {
            return $this->getAttributeSelectEnterprise($group, $attributes);
        }
    }

    /**
     * @return Select[]
     */
    protected function getAttributeSelects()
    {
        $selects = [];
        $attributeGroups = $this->getAttributeGroups();

        foreach ($attributeGroups as $group => $attributes) {
            if ($group == '_static') {
                foreach ($this->getStaticAttributeSelect($attributes) as $select) {
                    $selects[] = $select;
                }
            } else {
                $selects[] = $this->createEavAttributeGroupSelect($group, $attributes);
            }
        }

        if ($this->entityIds) {
            foreach ($selects as $select) {
                $select->where('entity_id IN (?)', $this->entityIds);
            }
        }

        return $selects;
    }
}