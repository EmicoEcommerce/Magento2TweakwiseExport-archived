<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2018
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Magento\Catalog\Model\ResourceModel\Product\Collection as ProductCollection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Db_Select;

class Price implements DecoratorInterface
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Config
     */
    private $config;

    /**
     * Price constructor.
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        Config $config
    )
    {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->config = $config;
    }

    /**
     * @param Collection $collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    public function decorate(Collection $collection)
    {
        $websiteId = $this->storeManager->getStore($collection->getStoreId())->getWebsiteId();
        $priceSelect = $this->createPriceSelect($collection->getIds(), $websiteId);

        $priceQuery = $priceSelect->getSelect()->query();

        while ($row = $priceQuery->fetch()) {
            $entityId = $row['entity_id'];
            $row['price'] = $this->getPriceValue($collection->getStoreId(), $row);
            $collection->get($entityId)->setFromArray($row);
        }
    }

    /**
     * @param array $ids
     * @param int $websiteId
     * @return ProductCollection
     */
    protected function createPriceSelect(array $ids, int $websiteId): ProductCollection
    {
        $priceSelect = $this->collectionFactory->create();
        $priceSelect
            ->addAttributeToFilter('entity_id', ['in' => $ids])
            ->addPriceData(0, $websiteId)
            ->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(
                [
                    'entity_id',
                    'price' => 'price_index.price',
                    'final_price' => 'price_index.final_price',
                    'min_price' => 'price_index.min_price',
                    'max_price' => 'price_index.max_price'
                ]
            );

        return $priceSelect;
    }

    /**
     * @param int $storeId
     * @param array $priceData
     * @return float
     */
    protected function getPriceValue(int $storeId, array $priceData): float
    {
        $priceFields = $this->config->getPriceFields($storeId);
        foreach ($priceFields as $field) {
            $value = isset($priceData[$field]) ? (float)$priceData[$field] : 0;
            if ($value > 0.00001) {
                return $value;
            }
        }

        return 0;
    }
}