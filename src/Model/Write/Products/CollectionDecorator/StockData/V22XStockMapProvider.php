<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator\StockData;

use Emico\TweakwiseExport\Model\StockItem;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Emico\TweakwiseExport\Model\StockItemFactory as TweakwiseStockItemFactory;

class V22XStockMapProvider implements StockMapProviderInterface
{
    /**
     * @var StockItemRepositoryInterface
     */
    private $stockItemRepository;

    /**
     * @var StockItemCriteriaInterfaceFactory
     */
    private $criteriaFactory;

    /**
     * @var TweakwiseStockItemFactory
     */
    private $tweakwiseStockItemFactory;

    /**
     * StockData constructor.
     *
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockItemCriteriaInterfaceFactory $criteriaFactory
     * @param TweakwiseStockItemFactory $tweakwiseStockItemFactory
     */
    public function __construct(
        StockItemRepositoryInterface $stockItemRepository,
        StockItemCriteriaInterfaceFactory $criteriaFactory,
        TweakwiseStockItemFactory $tweakwiseStockItemFactory
    )
    {
        $this->stockItemRepository = $stockItemRepository;
        $this->criteriaFactory = $criteriaFactory;
        $this->tweakwiseStockItemFactory = $tweakwiseStockItemFactory;
    }

    /**
     * @param Collection $collection
     * @param int $storeId
     * @return StockItemInterface[]
     */
    public function getStockItemMap(Collection $collection, int $storeId): array
    {
        if ($collection->count() === 0) {
            return [];
        }

        $entityIds = $collection->getAllIds();

        $criteria = $this->criteriaFactory->create();
        $criteria->setProductsFilter([$entityIds]);
        $items = $this->stockItemRepository->getList($criteria)->getItems();

        $map = [];
        foreach ($items as $item) {
            $productId = (int) $item->getProductId();
            $tweakwiseStockItem = $this->getTweakwiseStockItem($item);
            $map[$productId] = $tweakwiseStockItem;
        }
        return $map;
    }

    /**
     * @param StockItemInterface $item
     * @return StockItem
     */
    protected function getTweakwiseStockItem(StockItemInterface $item)
    {
        $tweakwiseStockItem = $this->tweakwiseStockItemFactory->create();
        $tweakwiseStockItem->setQty((int)$item->getQty());
        $tweakwiseStockItem->setIsInStock((int)$item->getIsInStock() || !$item->getManageStock());

        return $tweakwiseStockItem;
    }
}