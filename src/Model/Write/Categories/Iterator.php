<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Model\Write\Categories;

use Emico\TweakwiseExport\Model\Helper;
use Emico\TweakwiseExport\Model\Write\EavIterator;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\DB\Select;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use Zend_Db_Expr;

class Iterator extends EavIterator
{
    /**
     * @return array
     */
    protected function getEavSelectOrder(): array
    {
        return [
            'path',
            $this->getIdentifierField(),
            'store_id',
        ];
    }

    /**
     * Iterator constructor.
     *
     * @param Helper $helper
     * @param EavConfig $eavConfig
     * @param DbContext $dbContext
     * @param array|string[] $attributes
     */
    public function __construct(Helper $helper, EavConfig $eavConfig, DbContext $dbContext, $attributes)
    {
        parent::__construct($helper, $eavConfig, $dbContext, 'catalog_category', $attributes);
    }

    /**
     * {@inheritdoc}
     */
    protected function getStaticAttributeSelect(array $attributes): array
    {
        if (!$this->helper->isEnterprise()) {
            $selects = parent::getStaticAttributeSelect($attributes);
        } else {
            $selects = $this->getEnterpriseStaticAttributeSelect($attributes);
        }

        foreach ($selects as $select) {
            $select->columns($this->getConnection()->getTableName('catalog_category_entity') . '.path');
        }

        return $selects;
    }

    /**
     * @param array $attributes
     * @return array
     */
    protected function getEnterpriseStaticAttributeSelect(array $attributes): array
    {
        $connection = $this->getConnection();

        $selects = [];
        foreach ($attributes as $attributeKey => $attribute) {
            if ($attributeKey === 'parent_id') {
                $select = $this->getParentAttributeSelect($attribute);
                $selects[] = $select;
                continue;
            }
            $attributeExpression = new Zend_Db_Expr($connection->quote($attributeKey));
            $select = $connection->select()
                ->from(
                    $attribute->getBackendTable(),
                    [
                        $this->getIdentifierField(),
                        'store_id' => new Zend_Db_Expr('0'),
                        'attribute_id' => $attributeExpression,
                        'value' => $attribute->getAttributeCode()
                    ]
                );
            $selects[] = $select;
        }

        return $selects;
    }

    /**
     * @param $attribute
     */
    protected function getParentAttributeSelect($attribute)
    {
        $connection = $this->getConnection();
        $attributeExpression = new Zend_Db_Expr($connection->quote('parent_id'));
        $select = $connection->select()
            ->from($attribute->getBackendTable())
            ->reset('columns')
            ->columns([
                $this->getIdentifierField(),
                'store_id' => new Zend_Db_Expr('0'),
                'attribute_id' => $attributeExpression,
            ]);
        $select->join(
                ['parent_ids' => $attribute->getBackendTable()],
                'catalog_category_entity.parent_id = parent_ids.entity_id',
                ['value' => 'parent_ids.row_id']
            );

        return $select;

    }

    /**
     * {@inheritdoc}
     */
    protected function createEavAttributeGroupSelect(string $group, array $attributes): Select
    {
        $select = parent::createEavAttributeGroupSelect($group, $attributes);
        $identifier = $this->getIdentifierField();

        /** @var AbstractAttribute $staticAttribute */
        $staticAttribute = reset($this->getAttributeGroups()['_static']);
        /** @var AbstractAttribute $eavAttribute */
        $eavAttribute = reset($attributes);

        $select->join(
            $staticAttribute->getBackendTable(),
            $staticAttribute->getBackendTable() . ".{$identifier} = " . $eavAttribute->getBackendTable() . ".{$identifier}",
            ['path']
        );


        return $select;
    }
}