<?php

/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2020 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Model\CombinedStock;

use Emico\TweakwiseExport\Model\StockItem;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;

class CombinedStockItemProvider implements CombinedStockItemInterface
{
    /**
     * @var CombinedStockItemInterface
     */
    protected $simpleInstance;

    /**
     * @var CombinedStockItemInterface[]
     */
    protected $productTypes = [];

    /**
     * CombinedStockItemProvider constructor.
     * @param Simple $simpleInstance
     * @param CombinedStockItemInterface[] $productTypes
     */
    public function __construct(Simple $simpleInstance, array $productTypes)
    {
        $this->simpleInstance = $simpleInstance;
        $this->productTypes = $productTypes;
    }

    /**
     * @param ExportEntity $exportEntity
     * @return StockItem
     */
    public function getCombinedStockItem(ExportEntity $exportEntity): StockItem
    {
        return $this->getInstanceByProductType($exportEntity)->getCombinedStockItem($exportEntity);
    }

    /**
     * @param ExportEntity $exportEntity
     * @return CombinedStockItemInterface
     */
    protected function getInstanceByProductType(ExportEntity $exportEntity): CombinedStockItemInterface
    {
        if (!$exportEntity->isComposite() || !isset($this->productTypes[$exportEntity->getTypeId()])) {
            return $this->simpleInstance;
        }

        return $this->productTypes[$exportEntity->getTypeId()];
    }
}
