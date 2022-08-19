<?php

namespace Tweakwise\Magento2TweakwiseExport\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Simplexml\Element;
use Magento\InventorySalesApi\Api\StockResolverInterface;

/**
 * This is necessary to remain compatible with Magento 2.2.X
 * setup:di:compile fails when there is a reference to a non existing Interface or Class in the constructor
 *
 * Class StockResolverFactory
 * @package Tweakwise\Magento2TweakwiseExport\Model
 */
class StockResolverFactory
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
     * @return StockResolverInterface
     */
    public function create($sourceData = null)
    {
        return $this->_objectManager->create(StockResolverInterface::class, ['sourceData' => $sourceData]);
    }
}
