<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Model\Write;

use AppendIterator;
use IteratorAggregate;
use IteratorIterator;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
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
     * EavIterator constructor.
     *
     * @param EavConfig $eavConfig
     * @param string $entityCode
     * @param string[] $attributes
     */
    public function __construct(EavConfig $eavConfig, $entityCode, array $attributes)
    {
        $this->eavConfig = $eavConfig;
        $this->entityCode = $entityCode;
        $this->attributes = $attributes;
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
    protected function getAttributeSelect(AbstractAttribute $attribute)
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
                $selects[] = $this->getStaticAttributeSelect($attribute);
            } else {
                $selects[] = $this->getAttributeSelect($attribute);
            }
        }
        return $selects;
    }
}