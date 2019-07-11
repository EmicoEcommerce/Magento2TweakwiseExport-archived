<?php
/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2019.
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator\StockData;


use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Config\Source\StockCalculation;
use Emico\TweakwiseExport\Model\StockItem;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator\DecoratorInterface;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\SourceItemRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Emico\TweakwiseExport\Model\StockItemFactory as TweakwiseStockItemFactory;


class V23X implements DecoratorInterface
{

    /**
     * @var SourceItemRepositoryInterface
     */
    private $sourceItemRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $criteriaBuilder;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var SourceItemInterfaceFactory
     */
    private $sourceItemFactory;

    /**
     * @var TweakwiseStockItemFactory
     */
    private $tweakwiseStockItemFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * StockData constructor.
     *
     * @param SourceItemRepositoryInterface $sourceItemRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param StockItemInterfaceFactory $stockItemFactory
     * @param Config $config
     */
    public function __construct(
        SourceItemRepositoryInterface $sourceItemRepository,
        SearchCriteriaBuilder $criteriaBuilder,
        TweakwiseStockItemFactory $tweakwiseStockItemFactory,
        StoreManagerInterface $storeManager,
        Config $config
    )
    {
        $this->sourceItemRepository = $sourceItemRepository;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->storeManager = $storeManager;
        $this->config = $config;
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
        $storeCode = $this->storeManager->getStore($collection->getStoreId())->getCode();
        $stockItemMap = $this->getStockItemMap($collection->getAllSkus(), $storeCode);
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
        /** @var string $sku */
        $sku = $entity->getAttribute('sku', false);
        if (isset($stockItemMap[$sku])) {
            $stockItem = $stockItemMap[$sku];
        } else {
            $stockItem = $this->tweakwiseStockItemFactory->create();
        }

        if (method_exists($stockItem, 'setStoreId')) {
            $stockItem->setStoreId($storeId);
        }

        $entity->setStockItem($stockItem);
    }

    /**
     * @param array $entityIds
     * @return SourceItemInterface[]
     */
    private function getStockItemMap(array $skus, string $storeCode): array
    {
        if (\count($skus) === 0) {
            return [];
        }

        $criteria = $this->criteriaBuilder
            ->addFilter('sku', $skus, 'in')
            ->addFilter('source_code', $storeCode)
            ->create();
        $items = $this->sourceItemRepository->getList($criteria)->getItems();

        $map = [];
        /** @var SourceItemInterface $item */
        foreach ($items as $item) {
            $sku = $item->getSku();
            $tweakwiseStockItem = $this->getTweakwiseStockItem($item);
            $map[$sku] = $tweakwiseStockItem;
        }
        return $map;
    }

    /**
     * @param SourceItemInterface $item
     * @return StockItem
     */
    protected function getTweakwiseStockItem(SourceItemInterface $item)
    {
        /** @var \Emico\TweakwiseExport\Model\StockItem $tweakwiseStockItem */
        $tweakwiseStockItem = $this->tweakwiseStockItemFactory->create();
        $tweakwiseStockItem->setQty((int)$item->getQuantity());
        $tweakwiseStockItem->setIsInStock((int)$item->getStatus());

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
     * @return float
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

        $inStockchildrenCount = \count(\array_filter($children, [$this, 'isInStock']));
        return round(($inStockchildrenCount / $childrenCount) * 100, 2);
    }

    /**
     * @param ExportEntity $entity
     * @return bool
     */
    private function isInStock(ExportEntity $entity): bool
    {
        $stockItem = $entity->getStockItem();

        if (!$stockItem) {
            return true;
        }

        return $stockItem->getIsInStock();
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