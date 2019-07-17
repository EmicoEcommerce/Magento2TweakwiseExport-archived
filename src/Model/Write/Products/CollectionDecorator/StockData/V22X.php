<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator\StockData;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Config\Source\StockCalculation;
use Emico\TweakwiseExport\Model\StockItem;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator\DecoratorInterface;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Emico\TweakwiseExport\Model\StockItemFactory as TweakwiseStockItemFactory;

class V22X implements DecoratorInterface
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
     * @var Config
     */
    private $config;

    /**
     * @var StockItemInterfaceFactory
     */
    private $stockItemFactory;

    /**
     * @var TweakwiseStockItemFactory
     */
    private $tweakwiseStockItemFactory;

    /**
     * StockData constructor.
     *
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockItemCriteriaInterfaceFactory $criteriaFactory
     * @param StockItemInterfaceFactory $stockItemFactory
     * @param Config $config
     */
    public function __construct(
        StockItemRepositoryInterface $stockItemRepository,
        StockItemCriteriaInterfaceFactory $criteriaFactory,
        StockItemInterfaceFactory $stockItemFactory,
        TweakwiseStockItemFactory $tweakwiseStockItemFactory,
        Config $config
    )
    {
        $this->stockItemRepository = $stockItemRepository;
        $this->criteriaFactory = $criteriaFactory;
        $this->config = $config;
        $this->stockItemFactory = $stockItemFactory;
        $this->tweakwiseStockItemFactory = $tweakwiseStockItemFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(Collection $collection)
    {
        // This has to be called before setting the stock items. This way the composite
        // products are not filtered since they mostly have 0 stock.
        $toBeCombinedEntities = $collection->getExported();
        $storeId = $collection->getStoreId();

        $this->addStockItems($storeId, $collection);
        foreach ($toBeCombinedEntities as $item) {
            $this->combineStock($item, $storeId);
            $this->addStockPercentage($item, $storeId);
        }
    }

    /**
     * @param int $storeId
     * @param Collection $collection
     */
    private function addStockItems(int $storeId, Collection $collection)
    {
        if ($collection->count() === 0) {
            return;
        }

        $stockItemMap = $this->getStockItemMap($collection->getAllIds());
        foreach ($collection as $entity) {
            $this->assignStockItem($stockItemMap, $storeId, $entity);

            foreach ($entity->getExportChildren() as $childEntity) {
                $this->assignStockItem($stockItemMap, $storeId, $childEntity);
            }
        }
    }

    /**
     * @param array $stockItemMap
     * @param int $storeId
     * @param ExportEntity $entity
     */
    private function assignStockItem(array $stockItemMap, int $storeId, ExportEntity $entity)
    {
        $entityId = $entity->getId();
        if (isset($stockItemMap[$entityId])) {
            $stockItem = $stockItemMap[$entityId];
        } else {
            $stockItem = $this->stockItemFactory->create();
        }

        if (method_exists($stockItem, 'setStoreId')) {
            $stockItem->setStoreId($storeId);
        }

        $entity->setStockItem($stockItem);
    }

    /**
     * @param array $entityIds
     * @return StockItemInterface[]
     */
    private function getStockItemMap(array $entityIds): array
    {
        if (\count($entityIds) === 0) {
            return [];
        }

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

    /**
     * @param ExportEntity $entity
     * @param int $storeId
     */
    private function combineStock(ExportEntity $entity, int $storeId)
    {
        if (!$entity->isComposite()) {
            return;
        }

        $combinedStock = $this->getCombinedStock($entity, $storeId);
        $entity->getStockItem()->setQty($combinedStock);
    }

    /**
     * @param ExportEntity $entity
     * @param int $storeId
     */
    private function addStockPercentage(ExportEntity $entity, int $storeId)
    {
        if (!$this->config->isStockPercentage($storeId)) {
            return;
        }

        $entity->addAttribute('stock_percentage', $this->calculateStockPercentage($entity));
    }

    /**
     * @param ExportEntity $entity
     * @return float
     */
    private function calculateStockPercentage(ExportEntity $entity): float
    {
        if (!$entity->isComposite()) {
            return (int) $this->isInStock($entity) * 100;
        }

        $children = $entity->getExportChildrenIncludeOutOfStock();
        $childrenCount = \count($children);
        // Just to be sure we dont divide by 0, we really should not get here
        if ($childrenCount <= 0) {
            return (int) $this->isInStock($entity) * 100;
        }

        $inStockChildrenCount = \count(\array_filter($children, [$this, 'isInStock']));
        return round(($inStockChildrenCount / $childrenCount) * 100, 2);
    }

    /**
     * @param ExportEntity $entity
     * @return bool
     */
    private function isInStock(ExportEntity $entity): bool
    {
        $stockItem = $entity->getStockItem();
        return !$stockItem || $stockItem->getIsInStock();
    }

    /**
     * @param ExportEntity $entity
     * @param int $storeId
     * @return float
     */
    private function getCombinedStock(ExportEntity $entity, int $storeId): float
    {
        $stockQuantities = $this->getStockQuantities($entity);
        if (empty($stockQuantities)) {
            return 0;
        }

        switch ($this->config->getStockCalculation($storeId)) {
            case StockCalculation::OPTION_MAX:
                return max($stockQuantities);
            case StockCalculation::OPTION_MIN:
                return min($stockQuantities);
            case StockCalculation::OPTION_SUM:
            default:
                return array_sum($stockQuantities);
        }
    }

    /**
     * @param ExportEntity $entity
     * @return float[]
     */
    private function getStockQuantities(ExportEntity $entity): array
    {
        $stockQty = [];
        foreach ($entity->getExportChildren() as $child) {
            $stockQty[] = $child->getStockQty();
        }

        return $stockQty;
    }
}