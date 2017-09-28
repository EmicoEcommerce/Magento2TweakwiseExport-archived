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
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;

class Iterator extends EavIterator
{
    /**
     * {@inheritDoc}
     */
    protected $eavSelectOrder = [
        'path',
        'entity_id',
        'store_id',
    ];

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
    protected function getStaticAttributeSelect(array $attributes)
    {
        $selects = parent::getStaticAttributeSelect($attributes);

        foreach ($selects as $select) {
            $select->columns('path');
        }

        return $selects;
    }

    /**
     * {@inheritdoc}
     */
    protected function createEavAttributeGroupSelect($group, array $attributes)
    {
        $select = parent::createEavAttributeGroupSelect($group, $attributes);
        $select->columns('main_table.path');

        return $select;
    }
}