<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Writer\Categories;

use AppendIterator;
use ArrayIterator;
use IteratorAggregate;

class Iterator implements IteratorAggregate
{
    /**
     *{@inheritdoc}
     */
    public function getIterator()
    {
        $iterator = new AppendIterator();
        $iterator->append(new ArrayIterator([['store_id' => 0, 'tweakwise_id' => '1', 'name' => 'Root', 'position' => 0]]));

        /** @var Zend_Db_Select[] $selects */
        $selects = array();

        $appEmulation = Mage::getSingleton('core/app_emulation');
        /** @var $store Mage_Core_Model_Store */
        foreach (Mage::app()->getStores() as $store) {
            if (!$store->getIsActive() || !$helper->isEnabled($store)) {
                continue;
            }

            //Start environment emulation of the specified store
            $initialEnvironmentInfo = $appEmulation->startEnvironmentEmulation($store->getId());

            $selects[] = $this->getCategoryQuery($store);
            //Stop environment emulation and restore original store
            $appEmulation->stopEnvironmentEmulation($initialEnvironmentInfo);
        }

        /** @var Varien_Db_Statement_Pdo_Mysql $stmt */
        $stmt = Mage::getResourceModel('catalog/category_flat_collection')
            ->getConnection()
            ->select()
            ->union($selects)
            ->order('store_id')
            ->order('level')
            ->query();

        $iterator->append(new IteratorIterator($stmt));

        return new Emico_TweakwiseExport_Model_Writer_CategoryFilterIterator($iterator);
    }
}