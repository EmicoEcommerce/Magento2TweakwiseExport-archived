<?php

/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2020.
 */
declare(strict_types=1);


namespace Emico\TweakwiseExport\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Simplexml\Element;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;

/**
 * This is necessary to remain compatible with Magento 2.2.X
 * setup:di:compile fails when there is a reference to a non existing Interface or Class in the constructor
 *
 * Class StockSourceProviderFactory
 * @package Emico\TweakwiseExport\Model
 */
class StockSourceProviderFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * Create config model
     * @param string|Element $sourceData
     * @return GetSourcesAssignedToStockOrderedByPriorityInterface
     */
    public function create($sourceData = null)
    {
        return $this->_objectManager->create(GetSourcesAssignedToStockOrderedByPriorityInterface::class, ['sourceData' => $sourceData]);
    }
}
