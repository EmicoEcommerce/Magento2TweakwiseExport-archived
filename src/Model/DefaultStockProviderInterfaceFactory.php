<?php

/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2020.
 */

namespace Emico\TweakwiseExport\Model;

use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Simplexml\Element;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;

class DefaultStockProviderInterfaceFactory
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
     * @return DefaultStockProviderInterface
     */
    public function create($sourceData = null)
    {
        return $this->_objectManager->create(DefaultStockProviderInterface::class, ['sourceData' => $sourceData]);
    }
}