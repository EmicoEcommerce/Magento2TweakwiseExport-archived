<?php

/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2020.
 */

namespace Emico\TweakwiseExport\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Simplexml\Element;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;

/**
 * This is necessary to remain compatible with Magento 2.2.X
 * setup:di:compile fails when there is a reference to a non existing Interface or Class in the constructor
 *
 * Class StockResolverFactory
 * @package Emico\TweakwiseExport\Model
 */
class DefaultStockProviderInterfaceFactory
{
    /**
     * @var ObjectManagerInterface
     */
    protected ObjectManagerInterface $_objectManager;

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
     * @return DefaultStockProviderInterface
     */
    public function create($sourceData = null): DefaultStockProviderInterface
    {
        return $this->_objectManager->create(DefaultStockProviderInterface::class, ['sourceData' => $sourceData]);
    }
}
