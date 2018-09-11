<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Config\Source\StockCalculation;
use Emico\TweakwiseExport\Model\Helper;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;
use Magento\CatalogInventory\Api\Data\StockItemInterface;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;

class StockData extends AbstractDecorator
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
     * @var Helper
     */
    private $helper;

    /**
     * StockData constructor.
     *
     * @param DbContext $dbContext
     * @param StockItemRepositoryInterface $stockItemRepository
     * @param StockItemCriteriaInterfaceFactory $criteriaFactory
     * @param StockItemInterfaceFactory $stockItemFactory
     * @param Config $config
     * @param Helper $helper
     */
    public function __construct(
        DbContext $dbContext,
        StockItemRepositoryInterface $stockItemRepository,
        StockItemCriteriaInterfaceFactory $criteriaFactory,
        StockItemInterfaceFactory $stockItemFactory,
        Config $config,
        Helper $helper
    )
    {
        parent::__construct($dbContext);
        $this->stockItemRepository = $stockItemRepository;
        $this->criteriaFactory = $criteriaFactory;
        $this->config = $config;
        $this->stockItemFactory = $stockItemFactory;
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(Collection $collection)
    {
        // This has to be called before setting the stock items. This way the composite
        // products are not filtered since they mostly have 0 stock.
        $toBeCombinedEntities = $collection->getExported();

        $this->addStockItems($collection->getStoreId(), $collection);
        foreach ($toBeCombinedEntities as $item) {
            $this->combineStock($item, $collection->getStoreId());
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
        if ($this->helper->isEnterprise()) {
            return $this->getStockItemMapEnterprise($entityIds);
        }

        return $this->getStockItemMapCommunity($entityIds);
    }

    /**
     * @param array $entityIds
     * @return array
     */
    private function getStockItemMapCommunity(array $entityIds): array
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
            $map[$productId] = $item;
        }
        return $map;
    }

    /**
     * @param array $entityIds
     * @return array
     */
    private function getStockItemMapEnterprise(array $entityIds): array
    {
        if (\count($entityIds) === 0) {
            return [];
        }

        $entityRowIdMap = $this->getEntityIdRowIdMap($entityIds);
        $entityIds = array_keys($entityRowIdMap);

        $criteria = $this->criteriaFactory->create();
        $criteria->setProductsFilter([$entityIds]);
        $items = $this->stockItemRepository->getList($criteria)->getItems();

        $map = [];
        foreach ($items as $item) {
            $productId = (int) $item->getProductId();
            $map[$entityRowIdMap[$productId]] = $item;
        }
        return $map;
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
    private function getCombinedStock(ExportEntity $entity, int $storeId): float
    {
        $stockQty = [];
        foreach ($entity->getExportChildren() as $child) {
            $stockQty[] = $child->getStockQty();
        }

        switch ($this->config->getStockCalculation($storeId)) {
            case StockCalculation::OPTION_MAX:
                return max($stockQty);
            case StockCalculation::OPTION_MIN:
                return min($stockQty);
            case StockCalculation::OPTION_SUM:
            default:
                return array_sum($stockQty);
        }
    }
}