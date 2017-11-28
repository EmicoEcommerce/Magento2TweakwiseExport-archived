<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Config\Source\StockCalculation;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;
use Magento\CatalogInventory\Api\Data\StockItemInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemCriteriaInterfaceFactory;
use Magento\CatalogInventory\Api\StockItemRepositoryInterface;

class StockData implements DecoratorInterface
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
        Config $config
    )
    {
        $this->stockItemRepository = $stockItemRepository;
        $this->criteriaFactory = $criteriaFactory;
        $this->config = $config;
        $this->stockItemFactory = $stockItemFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(Collection $collection)
    {
        $entities = $collection->getExportedIncludingChildren();

        $this->addStockItems($collection->getStoreId(), $entities);
        foreach ($entities as $item) {
            $this->combineStock($item, $collection->getStoreId());
        }
    }

    /**
     * @param int $storeId
     * @param ExportEntity[] $entities
     */
    private function addStockItems(int $storeId, array $entities)
    {
        $criteria = $this->criteriaFactory->create();
        $criteria->setProductsFilter(array_keys($entities));

        $items = $this->stockItemRepository->getList($criteria)->getItems();
        foreach ($items as $item) {
            $productId = (int) $item->getProductId();
            $entities[$productId]->setStockItem($item);

            if (method_exists($item, 'setStoreId')) {
                $item->setStoreId($storeId);
            }

            unset($entities[$productId]);
        }

        foreach ($entities as $entityWithoutStock) {
            $stockItem = $this->stockItemFactory->create();
            $entityWithoutStock->setStockItem($stockItem);
        }
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