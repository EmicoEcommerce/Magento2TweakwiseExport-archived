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
use Magento\Framework\Event\Manager;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use Emico\TweakwiseExport\Model\Config as EmicoConfig;

/**
 * Class Iterator
 * @package Emico\TweakwiseExport\Model\Write\Categories
 */
class Iterator extends EavIterator
{
    /**
     * @var array
     */
    protected $entityBatchOrder = [
        'level',
        'path'
    ];

    /**
     * {@inheritDoc}
     */
    protected $eavSelectOrder = [
        'path',
        'entity_id',
        'store_id'
    ];

    /**
     * Iterator constructor.
     *
     * @param int $batchSize
     * @param Helper $helper
     * @param EavConfig $eavConfig
     * @param DbContext $dbContext
     * @param array|string[] $attributes
     * @param EmicoConfig $config
     */
    public function __construct(
        Helper $helper,
        EavConfig $eavConfig,
        DbContext $dbContext,
        Manager $eventManager,
        $attributes,
        EmicoConfig $config
    ) {
        parent::__construct(
            $helper,
            $eavConfig,
            $dbContext,
            $eventManager,
            'catalog_category',
            $attributes,
            $config->getBatchSizeCategories()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getStaticAttributeSelect(array $attributes): array
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
    protected function createEavAttributeGroupSelect(string $group, array $attributes): Select
    {
        $select = parent::createEavAttributeGroupSelect($group, $attributes);

        if ($this->helper->isEnterprise()) {
            $select->columns('main_table.path');
        } else {
            /** @var AbstractAttribute $staticAttribute */
            $staticAttribute = reset($this->getAttributeGroups()['_static']);

            /** @var AbstractAttribute $eavAttribute */
            $eavAttribute = reset($attributes);

            $select->join(
                $staticAttribute->getBackendTable(),
                $staticAttribute->getBackendTable() . '.entity_id = ' . $eavAttribute->getBackendTable() . '.entity_id',
                ['path']
            );
        }

        return $select;
    }
}
