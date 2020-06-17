<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\CombinedStock\CombinedStockItemInterface;
use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator\StockData\StockMapProviderInterface;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;
use Magento\Framework\App\ProductMetadataInterface;
use Emico\TweakwiseExport\Model\StockItemFactory as TweakwiseStockItemFactory;
use Magento\Framework\Module\Manager;

class StockData implements DecoratorInterface
{
    /**
     * @var ProductMetadataInterface
     */
    protected $metaData;

    /**
     * @var DecoratorInterface[]
     */
    protected $stockMapProviders = [];

    /**
     * @var TweakwiseStockItemFactory
     */
    protected $stockItemFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Manager
     */
    protected $moduleManager;

    /**
     * @var CombinedStockItemInterface
     */
    private $combinedStockItemProvider;

    /**
     * StockData constructor.
     *
     * @param ProductMetadataInterface $metaData
     * @param TweakwiseStockItemFactory $stockItemFactory
     * @param Config $config
     * @param Manager $moduleManager
     * @param CombinedStockItemInterface $combinedStockItemProvider
     * @param StockMapProviderInterface[] $stockMapProviders
     */
    public function __construct(
        ProductMetadataInterface $metaData,
        TweakwiseStockItemFactory $stockItemFactory,
        Config $config,
        Manager $moduleManager,
        CombinedStockItemInterface $combinedStockItemProvider,
        array $stockMapProviders
    ) {
        $this->metaData = $metaData;
        $this->stockMapProviders = $stockMapProviders;
        $this->stockItemFactory = $stockItemFactory;
        $this->config = $config;
        $this->moduleManager = $moduleManager;
        $this->combinedStockItemProvider = $combinedStockItemProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(Collection $collection)
    {
        // This has to be called before setting the stock items.
        // This way the composite products are not filtered since they mostly have 0 stock.
        $toBeCombinedEntities = $collection->getExported();
        $storeId = $collection->getStoreId();

        $this->addStockItems($storeId, $collection);
        foreach ($toBeCombinedEntities as $item) {
            $this->combineStock($item);
            $this->addStockPercentage($item);
        }
    }

    /**
     * This registers stock items to export entities, they will be combined later for "final"
     * stock items
     *
     * @param int $storeId
     * @param Collection $collection
     */
    protected function addStockItems(int $storeId, Collection $collection)
    {
        if ($collection->count() === 0) {
            return;
        }

        $stockMapProvider = $this->resolveStockMapProvider();
        $stockItemMap = $stockMapProvider->getStockItemMap($collection, $storeId);

        foreach ($collection as $entity) {
            $this->assignStockItem($stockItemMap, $entity);

            foreach ($entity->getExportChildren() as $childEntity) {
                $this->assignStockItem($stockItemMap, $childEntity);
            }
        }
    }

    /**
     * @param array $stockItemMap
     * @param ExportEntity $entity
     */
    protected function assignStockItem(array $stockItemMap, ExportEntity $entity)
    {
        $entityId = $entity->getId();
        if (isset($stockItemMap[$entityId])) {
            $stockItem = $stockItemMap[$entityId];
        } else {
            $stockItem = $this->stockItemFactory->create();
        }

        $entity->setStockItem($stockItem);
    }

    /**
     * @param ExportEntity $entity
     * @param int $storeId
     */
    private function combineStock(ExportEntity $entity)
    {
        $combinedStockItem = $this->combinedStockItemProvider->getCombinedStockItem($entity);
        $entity->setStockItem($combinedStockItem);
    }

    /**
     * @param ExportEntity $entity
     */
    protected function addStockPercentage(ExportEntity $entity)
    {
        $entity->addAttribute('stock_percentage', $this->calculateStockPercentage($entity));
    }

    /**
     * @param ExportEntity $entity
     * @return float
     */
    protected function calculateStockPercentage(ExportEntity $entity): float
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
    protected function isInStock(ExportEntity $entity): bool
    {
        $stockItem = $entity->getStockItem();
        return (int)(!$stockItem || $stockItem->getIsInStock());
    }

    /**
     * This method determines which inventory implementation is used
     * the options are the old magento stock items
     * or the new magento MSI with source items and reservations
     *
     * @return StockMapProviderInterface
     */
    protected function resolveStockMapProvider(): StockMapProviderInterface
    {
        $version = $this->metaData->getVersion();
        // In case of magento 2.2.X use magento stock items
        if (version_compare($version, '2.3.0', '<')) {
            return $this->stockMapProviders['stockItemMapProvider'];
        }
        // If 2.3.X but MSI is disabled also use stock items
        if (!$this->moduleManager->isEnabled('Magento_Inventory') || !$this->moduleManager->isEnabled('Magento_InventoryApi')) {
            return $this->stockMapProviders['stockItemMapProvider'];
        }

        // Use sourceItems to determine stock
        return $this->stockMapProviders['sourceItemMapProvider'];
    }
}
