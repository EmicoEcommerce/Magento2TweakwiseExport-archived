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
use Magento\Framework\App\ProductMetadata as CommunityProductMetadata;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\DB\Statement\Pdo\Mysql as MysqlStatement;
use Zend_Db_Expr;

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
     * @var string[]
     */
    protected $attributes = [];

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
     * EavIterator constructor.
     *
     * @param ProductMetadataInterface $productMetadata
     * @param EavConfig $eavConfig
     * @param string $entityCode
     * @param string[] $attributes
     */
    public function __construct(ProductMetadataInterface $productMetadata, EavConfig $eavConfig, $entityCode, array $attributes)
    {
        $this->eavConfig = $eavConfig;
        $this->entityCode = $entityCode;
        $this->attributes = $attributes;
        $this->productMetadata = $productMetadata;
    }

    /**
     * @param string $attributeCode
     * @return $this
     */
    public function selectAttribute($attributeCode)
    {
        $attribute = $this->eavConfig->getAttribute($this->entityCode, $attributeCode);
        $this->attributes[$attribute->getAttributeCode()] = $attribute;
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
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $entityIdAttribute = $this->eavConfig->getAttribute('catalog_category', 'entity_id');

        /** @var MysqlStatement $stmt */
        $stmt = $entityIdAttribute->getResource()->getConnection()
            ->select()
            ->union($this->getAttributeSelects())
            ->order('entity_id')
            ->order('store_id')
            ->query();

        $entity = ['entity_id' => null];

        // Loop over all rows and combine them to one array for entity
        while ($row = $stmt->fetch()) {
            if ($entity['entity_id'] != $row['entity_id']) {
                // If current loop entity is new yield return this entity
                if ($entity['entity_id']) {
                    yield $entity;
                }

                $entity = [
                    'entity_id' => (int) $row['entity_id'],
                    $row['attribute'] => $row['value'],
                ];
            } else {
                // Add row to current looping entity
                $attributeCode = $row['attribute'];

                if (isset($entity[$attributeCode])) {
                    // Only override if store specific
                    if ($row['store_id'] > 0) {
                        $entity[$attributeCode] = $row['value'];
                    }
                } else {
                    $entity[$attributeCode] = $row['value'];
                }
            }
        }

        if ($entity['entity_id']) {
            yield $entity;
        }
    }

    /**
     * @param AbstractAttribute $attribute
     * @return Select
     */
    protected function getStaticAttributeSelect(AbstractAttribute $attribute)
    {
        $connection = $attribute->getResource()->getConnection();

        $attributeExpression = new Zend_Db_Expr($connection->quote($attribute->getAttributeCode()));
        $select = $connection->select()
            ->from(
                $attribute->getBackendTable(),
                [
                    'entity_id',
                    'store_id' => new Zend_Db_Expr('0'),
                    'attribute' => $attributeExpression,
                    'value' => $attribute->getAttributeCode()
                ]
            );

        return $select;
    }

    /**
     * @param AbstractAttribute $attribute
     * @return Select
     */
    protected function getAttributeSelectCommunity(AbstractAttribute $attribute)
    {
        $connection = $attribute->getResource()->getConnection();
        $attributeExpression = new Zend_Db_Expr($connection->quote($attribute->getAttributeCode()));
        $select = $connection->select()
            ->from(
                $attribute->getBackendTable(),
                [
                    'entity_id',
                    'store_id',
                    'attribute' => $attributeExpression,
                    'value'
                ]
            )
            ->where('attribute_id = ?', $attribute->getId());

        if ($this->storeId) {
            $select->where('store_id = 0 OR store_id = ?', $this->storeId);
        } else {
            $select->where('store_id = 0');
        }

        return $select;
    }

    /**
     * @param AbstractAttribute $attribute
     * @return Select
     */
    protected function getAttributeSelectEnterprise(AbstractAttribute $attribute)
    {
        $connection = $attribute->getResource()->getConnection();
        $attributeExpression = new Zend_Db_Expr($connection->quote($attribute->getAttributeCode()));
        $select = $connection->select()
            ->from(['attribute_table' => $attribute->getBackendTable()], [])
            ->join(['main_table' => $attribute->getEntityType()->getEntityTable()], 'attribute_table.row_id = main_table.row_id', [])
            ->columns([
                'entity_id' => 'main_table.entity_id',
                'store_id' => 'attribute_table.store_id',
                'attribute' => $attributeExpression,
                'value' => 'attribute_table.value'
            ])
            ->where('attribute_id = ?', $attribute->getId());

        if ($this->storeId) {
            $select->where('store_id = 0 OR store_id = ?', $this->storeId);
        } else {
            $select->where('store_id = 0');
        }

        return $select;
    }

    /**
     * @return Select[]
     */
    protected function getAttributeSelects()
    {
        $eavConfig = $this->eavConfig;
        $selects = [];
        foreach ($this->attributes as $attributeCode) {
            $attribute = $eavConfig->getAttribute($this->entityCode, $attributeCode);
            if ($attribute->isStatic()) {
                $select = $this->getStaticAttributeSelect($attribute);
            } elseif ($this->productMetadata->getEdition() == CommunityProductMetadata::PRODUCT_NAME) {
                $select = $this->getAttributeSelectCommunity($attribute);
            } else {
                $select = $this->getAttributeSelectEnterprise($attribute);
            }

            if ($this->entityIds) {
                $select->where('entity_id IN (?)', $this->entityIds);
            }

            $selects[] = $select;
        }
        return $selects;
    }
}