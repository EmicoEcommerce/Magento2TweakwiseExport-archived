<?php
/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2019.
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator\StockData;

use Emico\TweakwiseExport\Model\StockItem;
use Emico\TweakwiseExport\Model\StockItemFactory as TweakwiseStockItemFactory;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Emico\TweakwiseExport\Model\DbResourceHelper;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\InventoryApi\Api\Data\SourceInterface;
use Magento\InventoryApi\Api\Data\SourceItemInterface;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Db_Expr;

/**
 * Class DefaultImplementation
 * @package Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator\StockData
 */
class SourceItemMapProvider implements StockMapProviderInterface
{
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
     * @var DbResourceHelper
     */
    private $dbResource;

    /**
     * StockData constructor.
     *
     * @param DbResourceHelper $dbResource
     * @param GetSourcesAssignedToStockOrderedByPriorityInterface $stockSourceProvider
     * @param TweakwiseStockItemFactory $tweakwiseStockItemFactory
     * @param StoreManagerInterface $storeManager
     * @param StockResolverInterface $stockResolver
     * @param DbResourceHelper $resourceHelper
     */
    public function __construct(
        DbResourceHelper $dbResource,
        GetSourcesAssignedToStockOrderedByPriorityInterface $stockSourceProvider,
        TweakwiseStockItemFactory $tweakwiseStockItemFactory,
        StoreManagerInterface $storeManager,
        StockResolverInterface $stockResolver,
        DbResourceHelper $resourceHelper
    ) {
        $this->dbResource = $dbResource;
        $this->stockSourceProvider = $stockSourceProvider;
        $this->tweakwiseStockItemFactory = $tweakwiseStockItemFactory;
        $this->storeManager = $storeManager;
        $this->stockResolver = $stockResolver;
        $this->dbResource = $resourceHelper;
    }

    /**
     * @param Collection $collection
     * @param int $storeId
     * @return StockItem[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    public function getStockItemMap(Collection $collection, int $storeId): array
    {
        if ($collection->count() === 0) {
            return [];
        }

        $skus = $collection->getAllSkus();

        $sourceCodes = $this->getSourceCodesForStore($storeId);
        $stockId = $this->getStockIdForStoreId($storeId);

        $dbConnection = $this->dbResource->getConnection();

        $sourceItemTableName = $this->dbResource->getTableName('inventory_source_item');
        $reservationTableName = $this->dbResource->getTableName('inventory_reservation');
        $productTableName = $this->dbResource->getTableName('catalog_product_entity');
        $stockItemTableName = $this->dbResource->getTableName('cataloginventory_stock_item');

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
                ['product_entity_id' => "$productTableName.entity_id"]
            )
            ->join(
                $stockItemTableName,
                "$productTableName.entity_id = $stockItemTableName.product_id",
                [
                    'backorders',
                    'min_sale_qty'
                ]
            )
            ->columns([
                "$sourceItemTableName.sku",
                'qty' => new Zend_Db_Expr("$sourceItemTableName.quantity + IFNULL(SUM(`$reservationTableName`.`quantity`), 0)"),
                'is_in_stock' => "$sourceItemTableName.status"
            ])
            ->group(new Zend_Db_Expr("`$sourceItemTableName`.`sku`"));

        $result = $select->query();
        $map = [];

        while ($row = $result->fetch()) {
            $map[$row['product_entity_id']] = $this->getTweakwiseStockItem($row);
        }

        return $map;
    }

    /**
     * @param int $storeId
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getSourceCodesForStore(int $storeId)
    {
        $stockId = $this->getStockIdForStoreId($storeId);
        $sourceModels = $this->stockSourceProvider->execute($stockId);

        $sourceCodeMapper = static function (SourceInterface $source) {
            return $source->getSourceCode();
        };

        return array_map($sourceCodeMapper, $sourceModels);
    }

    /**
     * @param int $storeId
     * @return int|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
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
        /** @var StockItem $tweakwiseStockItem */
        $tweakwiseStockItem = $this->tweakwiseStockItemFactory->create();

        $qty = (int)$item['qty'];
        $isInStock = (int) (
            $item['backorders'] ||
            (
                $qty >= (int)$item['min_sale_qty'] &&
                (int)$item['is_in_stock'] &&
                $qty > 0
            )
        );

        $tweakwiseStockItem->setQty($qty);
        $tweakwiseStockItem->setIsInStock($isInStock);

        return $tweakwiseStockItem;
    }
}
