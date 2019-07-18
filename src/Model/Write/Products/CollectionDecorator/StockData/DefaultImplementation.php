<?php
/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2019.
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator\StockData;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Config\Source\StockCalculation;
use Emico\TweakwiseExport\Model\StockItem;
use Emico\TweakwiseExport\Model\StockItemFactory as TweakwiseStockItemFactory;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator\AbstractDecorator;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class V23X
 * @package Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator\StockData
 */
class DefaultImplementation extends AbstractDecorator
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var TweakwiseStockItemFactory
     */
    private $tweakwiseStockItemFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var StockResolverInterface
     */
    private $stockResolver;

    /**
     * @var GetSourcesAssignedToStockOrderedByPriorityInterface
     */
    private $stockSourceProvider;

    /**
     * StockData constructor.
     *
     * @param DbContext $dbContext
     * @param GetSourcesAssignedToStockOrderedByPriorityInterface $stockSourceProvider
     * @param TweakwiseStockItemFactory $tweakwiseStockItemFactory
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param Config $config
     */
    public function __construct(
        DbContext $dbContext,
        GetSourcesAssignedToStockOrderedByPriorityInterface $stockSourceProvider,
        TweakwiseStockItemFactory $tweakwiseStockItemFactory,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        Config $config
    ) {
        parent::__construct($dbContext);
        $this->stockSourceProvider = $stockSourceProvider;
        $this->tweakwiseStockItemFactory = $tweakwiseStockItemFactory;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->config = $config;
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
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    private function addStockItems(int $storeId, Collection $collection)
    {
        if ($collection->count() === 0) {
            return;
        }

        $stockItemMap = $this->getStockItemMap($collection->getAllSkus(), $storeId);
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
     * @param array $skus
     * @param int $storeId
     * @return StockItem[]
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    private function getStockItemMap(array $skus, int $storeId): array
    {
        if (\count($skus) === 0) {
            return [];
        }

        $sourceCodes = $this->getSourceCodesForStore($storeId);
        $stockId = $this->getStockIdForStoreId($storeId);

        $dbConnection = $this->getConnection();

        $sourceItemTableName = $this->getTableName('inventory_source_item');
        $reservationTableName = $this->getTableName('inventory_reservation');
        $productTableName = $this->getTableName('catalog_product_entity');
        $stockItemTableName = $this->getTableName('cataloginventory_stock_item');

        $select = $dbConnection
            ->select()
            ->from($sourceItemTableName)
            ->where("$sourceItemTableName.sku IN (?)", $skus)
            ->where("$sourceItemTableName.source_code IN (?)", $sourceCodes)
            ->reset('columns')
            ->joinLeft(
                $reservationTableName,
                "$reservationTableName.sku = $sourceItemTableName.sku AND $reservationTableName.stock_id = $stockId",
                []
            )
            ->join(
                $productTableName,
                "$sourceItemTableName.sku = $productTableName.sku",
                []
            )
            ->join(
                $stockItemTableName,
                "$productTableName.entity_id = $stockItemTableName.product_id AND $stockItemTableName.stock_id = $stockId",
                [
                    'backorders',
                    'min_sale_qty'
                ]
            )
            ->columns([
                "$sourceItemTableName.sku",
                'qty' => new \Zend_Db_Expr("$sourceItemTableName.quantity + IFNULL(SUM(`$reservationTableName`.`quantity`), 0)"),
                'is_in_stock' => "$sourceItemTableName.status"
            ])
            ->group(new \Zend_Db_Expr("`$sourceItemTableName`.`sku`"));
        $result = $select->query();

        $map = [];
        while ($row = $result->fetch()) {
            $map[$row['sku']] = $this->getTweakwiseStockItem($row);
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

        $combinedStockQty = $this->getCombinedStock($entity, $storeId);
        $combinedStockStockStatus = $this->getCombinedStockStatus($entity, $storeId);
        $entity->getStockItem()->setQty($combinedStockQty);
        $entity->getStockItem()->setIsInStock($combinedStockStockStatus);
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
     * @param int $storeId
     * @return float
     */
    private function getCombinedStockStatus(ExportEntity $entity): float
    {
        $stockStatus = $this->getStockStatus($entity);
        return !empty($stockStatus) ? max($stockStatus) : 0;
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

    /**
     * @param ExportEntity $entity
     * @return float[]
     */
    private function getStockStatus(ExportEntity $entity): array
    {
        $stockStatus = [];
        foreach ($entity->getExportChildren() as $child) {
            $stockStatus[] = $child->getStockItem()->getIsInStock();
        }

        return $stockStatus;
    }

    /**
     * @param int $storeId
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getSourceCodesForStore(int $storeId)
    {
        $stockId = $this->getStockIdForStoreId($storeId);
        $sourceModels = $this->stockSourceProvider->execute($stockId);

        $sourceCodeMapper = function (SourceInterface $source) {
            return $source->getSourceCode();
        };

        return array_map($sourceCodeMapper, $sourceModels);
    }

    /**
     * @param int $storeId
     * @return int|null
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getStockIdForStoreId(int $storeId)
    {
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
        return $this->stockResolver->execute('website', $websiteCode)->getStockId();
    }

    /**
     * @param SourceItemInterface $item
     * @return StockItem
     */
    protected function getTweakwiseStockItem(array $item)
    {
        /** @var \Emico\TweakwiseExport\Model\StockItem $tweakwiseStockItem */
        $tweakwiseStockItem = $this->tweakwiseStockItemFactory->create();
        $qty = (int)$item['qty'];
        $tweakwiseStockItem->setQty($qty);
        $isInStock = (int) (
            $item['backorders'] ||
            (
                $qty >= (int)$item['min_sale_qty'] &&
                (int)$item['is_in_stock'] &&
                $qty > 0
            )
        );
        $tweakwiseStockItem->setIsInStock($isInStock);

        return $tweakwiseStockItem;
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
        return (int)(!$stockItem || $stockItem->getIsInStock());
    }
}
