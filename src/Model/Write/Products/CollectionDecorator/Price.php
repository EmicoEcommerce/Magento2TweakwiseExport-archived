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
use Magento\Customer\Model\Group;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ProductMetadata;
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
     * @var ProductMetadata
     */
    private $magentoInfo;

    /**
     * Price constructor.
     * @param CollectionFactory $collectionFactory
     * @param StoreManagerInterface $storeManager
     * @param Config $config
     * @param ProductMetadata $magentoInfo
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        StoreManagerInterface $storeManager,
        Config $config,
        ProductMetadata $magentoInfo
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->magentoInfo = $magentoInfo;
    }

    /**
     * We split this according to version number. In 2.3.0 and beyond catalog rule prices are incorporated in the
     * catalog_product_price_index table hence we do not need to join the rule table for this data. Alas this is not
     * the case in < 2.3.0. At some point (when we drop support for old magento2 releases) we will remove this split.
     *
     * @param Collection $collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    public function decorate(Collection $collection)
    {
        if (version_compare($this->magentoInfo->getVersion(), '2.3.0', 'lt')) {
            $this->decorateLT230($collection);
        } else {
            $this->decorateGTEG230($collection);
        }
    }

    /**
     * @param Collection $collection
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Zend_Db_Statement_Exception
     */
    protected function decorateGTEG230(Collection $collection): void
    {
        $websiteId = $this->storeManager->getStore($collection->getStoreId())->getWebsiteId();
        $priceSelect = $this->createPriceSelect($collection, $websiteId);
        $this->updateProductCollection($priceSelect, $collection);
    }

    /**
     * @param Collection $collection
     * @throws \Zend_Db_Statement_Exception
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function decorateLT230(Collection $collection): void
    {
        $websiteId = $this->storeManager->getStore($collection->getStoreId())->getWebsiteId();
        $priceSelect = $this->createPriceSelect($collection, $websiteId);
        $priceSelect->getSelect()->joinLeft(
                ['crpp' => $priceSelect->getTable('catalogrule_product_price')],
                sprintf(
                    'e.entity_id = crpp.product_id AND crpp.website_id = %s AND crpp.customer_group_id = %s AND crpp.rule_date = %s',
                    $priceSelect->getConnection()->quote($websiteId),
                    $priceSelect->getConnection()->quote(Group::NOT_LOGGED_IN_ID),
                    $priceSelect->getConnection()->quote((new \DateTime())->format('Y-m-d'))
                ),
                ['rule_price' => 'crpp.rule_price']
            );

        $this->updateProductCollection($priceSelect, $collection);
    }

    /**
     * @param ProductCollection $priceSelect
     * @param Collection $collection
     * @throws \Zend_Db_Statement_Exception
     */
    protected function updateProductCollection(ProductCollection $priceSelect, Collection $collection): void
    {
        $collectionQuery = $priceSelect->getSelect()->query();

        while ($row = $collectionQuery->fetch()) {
            $entityId = $row['entity_id'];
            $row['price'] = $this->getPriceValue($collection->getStoreId(), $row);
            $collection->get($entityId)->setFromArray($row);
        }
    }

    /**
     * @param Collection $collection
     * @param int $websiteId
     * @return ProductCollection
     */
    protected function createPriceSelect(Collection $collection, int $websiteId): ProductCollection
    {
        $priceSelect = $this->collectionFactory->create();
        $priceSelect
            ->addAttributeToFilter('entity_id', ['in' => $collection->getIds()])
            ->addPriceData(0, $websiteId)
            ->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->columns(
                [
                    'entity_id',
                    'price' => 'price_index.price',
                    'final_price' => 'price_index.final_price',
                    'old_price' => 'price_index.price',
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
            $value = isset($priceData[$field]) ? (float) $priceData[$field] : 0;
            if ($value > 0.00001) {
                return $value;
            }
        }

        return 0;
    }
}