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
use Emico\TweakwiseExport\Model\StockSourceProviderFactory;
use Emico\TweakwiseExport\Model\StockResolverFactory;
use Emico\TweakwiseExport\Model\DefaultStockProviderInterfaceFactory;
use Magento\InventorySalesApi\Api\StockResolverInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\InventoryApi\Api\GetSourcesAssignedToStockOrderedByPriorityInterface;
use Magento\InventoryCatalogApi\Api\DefaultStockProviderInterface;
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
    protected $tweakwiseStockItemFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var StockResolverInterface
     */
    protected $stockResolver;

    /**
     * @var GetSourcesAssignedToStockOrderedByPriorityInterface
     */
    protected $stockSourceProvider;

    /**
     * @var StockSourceProviderFactory
     */
    protected $stockSourceProviderFactory;

    /**
     * @var StockResolverFactory
     */
    protected $stockResolverFactory;

    /**
     * @var DbResourceHelper
     */
    protected $dbResource;

    /**
     * @var DefaultStockProviderInterfaceFactory
     */
    protected $defaultStockProviderFactory;

    /**
     * @var DefaultStockProviderInterface
     */
    protected $defaultStockProvider;

    /**
     * StockData constructor.
     *
     * @param DbResourceHelper $dbResource
     * @param StockSourceProviderFactory $stockSourceProviderFactory
     * @param TweakwiseStockItemFactory $tweakwiseStockItemFactory
     * @param StoreManagerInterface $storeManager
     * @param StockResolverFactory $stockResolverFactory
     * @param DefaultStockProviderInterfaceFactory $defaultStockProviderFactory
     * @param DbResourceHelper $resourceHelper
     */
    public function __construct(
        DbResourceHelper $dbResource,
        StockSourceProviderFactory $stockSourceProviderFactory,
        TweakwiseStockItemFactory $tweakwiseStockItemFactory,
        StoreManagerInterface $storeManager,
        StockResolverFactory $stockResolverFactory,
        DefaultStockProviderInterfaceFactory $defaultStockProviderFactory,
        DbResourceHelper $resourceHelper
    ) {
        $this->dbResource = $dbResource;
        $this->stockSourceProviderFactory = $stockSourceProviderFactory;
        $this->tweakwiseStockItemFactory = $tweakwiseStockItemFactory;
        $this->storeManager = $storeManager;
        $this->stockResolverFactory = $stockResolverFactory;
        $this->dbResource = $resourceHelper;
        $this->defaultStockProviderFactory = $defaultStockProviderFactory;
    }

    /**
     * @param Collection $collection
     * @param int $storeId
     * @return StockItem[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    public function getStockItemMap(Collection $collection, int $storeId)
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
        $stockItemTable = $this->dbResource->getTableName('cataloginventory_stock_item');

        $reservationSelect = $dbConnection
            ->select()
            ->from($reservationTableName)
            ->where('stock_id = ?', $stockId)
            ->reset('columns')
            ->columns(
                [
                    'sku',
                    'stock_id',
                    'r_quantity' => "SUM(`$reservationTableName`.`quantity`)"
                ]
            )
            ->group("$reservationTableName.sku");

        // When stock id is default apparently the standard stock items are still used.
        // Todo We should check if we can use magento's api for this as this is feeling rather sensitive.
        if ($this->getDefaultStockProvider()->getId() !== $stockId) {
            $sourceItemSelect = $dbConnection
                ->select()
                ->from($sourceItemTableName)
                ->reset('columns')
                ->where("$sourceItemTableName.source_code IN (?)", $sourceCodes)
                ->columns(
                    [
                        'sku',
                        's_quantity' => "SUM($sourceItemTableName.quantity)",
                        's_status' => "MAX($sourceItemTableName.status)"
                    ]
                )
                ->group("$sourceItemTableName.sku");
        } else {
            $sourceItemSelect = $dbConnection
                ->select()
                ->from($stockItemTable)
                ->reset('columns')
                ->where("$stockItemTable.product_id IN (?)", $collection->getAllIds())
                /*
                $stock_id is in this case the default stock id (i.e. 1) this filter problably doesnt remove anything
                but it is here just to be sure.
                */
                ->where("$stockItemTable.stock_id = ?", $stockId)
                ->columns(
                    [
                        'product_id',
                        's_quantity' => "$stockItemTable.qty",
                        's_status' => "$stockItemTable.is_in_stock"
                    ]
                );
        }

        $select = $dbConnection
            ->select()
            ->from($productTableName)
            ->reset('columns');

        // When stock id is default apparently the standard stock items are still used.
        // Todo We should check if we can use magento's api for this as this is feeling rather sensitive.
        if ($this->getDefaultStockProvider()->getId() !== $stockId) {
            $select->joinLeft(
                ['s' => $sourceItemSelect],
                "s.sku = $productTableName.sku",
                []
            );
        } else {
            $select->joinLeft(
                ['s' => $sourceItemSelect],
                "s.product_id = $productTableName.entity_id",
                []
            );
        }

        $select->joinLeft(
            ['r' => $reservationSelect],
            "r.sku = $productTableName.sku AND r.stock_id = $stockId",
            []
        );
        $select->join(
            $stockItemTable,
            "$stockItemTable.product_id = $productTableName.entity_id",
            [
                'backorders',
            ]
        )
        ->where("$productTableName.sku IN (?)", $skus)
        ->columns(
            [
                'product_entity_id' => "$productTableName.entity_id",
                'qty' => new Zend_Db_Expr('COALESCE(s.s_quantity,0) + COALESCE(r.r_quantity,0)'),
                'is_in_stock' => 'COALESCE(s.s_status,0)'
            ]
        );

        $result = $select->query();
        $map = [];

        while ($row = $result->fetch()) {
            $map[$row['product_entity_id']] = $this->getTweakwiseStockItem($row);
        }

        return $map;
    }

    /**
     * @param int $storeId
     * @return array|null[]|string[]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getSourceCodesForStore(int $storeId): array
    {
        $stockId = $this->getStockIdForStoreId($storeId);
        $sourceModels = $this->getStockSourceProvider()->execute($stockId);

        $sourceCodeMapper = static function (SourceInterface $source) {
            return $source->getSourceCode();
        };

        return array_map($sourceCodeMapper, $sourceModels);
    }

    /**
     * This is necessary to remain compatible with Magento 2.2.X
     * setup:di:compile fails when there is a reference to a non existing Interface or Class in the constructor
     *
     * @return GetSourcesAssignedToStockOrderedByPriorityInterface
     */
    protected function getStockSourceProvider(): GetSourcesAssignedToStockOrderedByPriorityInterface
    {
        if (!$this->stockSourceProvider) {
            $this->stockSourceProvider = $this->stockSourceProviderFactory->create();
        }

        return $this->stockSourceProvider;
    }

    /**
     * @return DefaultStockProviderInterface
     */
    protected function getDefaultStockProvider(): DefaultStockProviderInterface
    {
        if (!$this->defaultStockProvider) {
            $this->defaultStockProvider = $this->defaultStockProviderFactory->create();
        }

        return $this->defaultStockProvider;
    }

    /**
     * @param int $storeId
     * @return int|null
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    protected function getStockIdForStoreId(int $storeId): ?int
    {
        $websiteId = $this->storeManager->getStore($storeId)->getWebsiteId();
        $websiteCode = $this->storeManager->getWebsite($websiteId)->getCode();
        return $this->getStockResolver()->execute('website', $websiteCode)->getStockId();
    }

    /**
     * This is necessary to remain compatible with Magento 2.2.X
     * setup:di:compile fails when there is a reference to a non existing Interface or Class in the constructor
     *
     * @return StockResolverInterface
     */
    protected function getStockResolver(): StockResolverInterface
    {
        if (!$this->stockResolver) {
            $this->stockResolver = $this->stockResolverFactory->create();
        }

        return $this->stockResolver;
    }

    /**
     * @param SourceItemInterface $item
     * @return StockItem
     */
    protected function getTweakwiseStockItem(array $item): StockItem
    {
        /** @var StockItem $tweakwiseStockItem */
        $tweakwiseStockItem = $this->tweakwiseStockItemFactory->create();

        $qty = (int)$item['qty'];
        $isInStock = max((int)$item['backorders'], (int)$item['is_in_stock']);

        $tweakwiseStockItem->setQty($qty);
        $tweakwiseStockItem->setIsInStock($isInStock);

        return $tweakwiseStockItem;
    }
}
