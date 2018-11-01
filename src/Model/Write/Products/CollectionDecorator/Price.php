<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory;
use Magento\Customer\Model\Group;
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
    public function __construct(CollectionFactory $collectionFactory, StoreManagerInterface $storeManager, Config $config)
    {
        $this->collectionFactory = $collectionFactory;
        $this->storeManager = $storeManager;
        $this->config = $config;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(Collection $collection)
    {
        $websiteId = $this->storeManager->getStore($collection->getStoreId())->getWebsiteId();

        $collectionSelect = $this->collectionFactory->create();
        $collectionSelect
            ->addAttributeToFilter('entity_id', ['in' => $collection->getIds()])
            ->addPriceData(0, $websiteId)
            ->getSelect()
            ->reset(Zend_Db_Select::COLUMNS)
            ->joinLeft(
                ['crpp' => $collectionSelect->getTable('catalogrule_product_price')],
                sprintf(
                    'e.entity_id = crpp.product_id AND crpp.website_id = %s AND crpp.customer_group_id = %s AND crpp.rule_date = %s',
                    $collectionSelect->getConnection()->quote($websiteId),
                    $collectionSelect->getConnection()->quote(Group::NOT_LOGGED_IN_ID),
                    $collectionSelect->getConnection()->quote((new \DateTime())->format('Y-m-d'))
                ),
                ['rule_price' => 'crpp.rule_price']
            )
            ->columns([
                'entity_id',
                'price' => 'price_index.price',
                'final_price' => 'price_index.final_price',
                'old_price' => 'price_index.price',
                'min_price' => 'price_index.min_price',
                'max_price' => 'price_index.max_price',
            ]);
        $collectionQuery = $collectionSelect->getSelect()->query();

        while ($row = $collectionQuery->fetch()) {
            $entityId = $row['entity_id'];
            $row['price'] = $this->getPriceValue($collection->getStoreId(), $row);
            $collection->get($entityId)->setFromArray($row);
        }
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
            $value = (float) $priceData[$field];
            if ($value > 0.00001) {
                return $value;
            }
        }

        return 0.0;
    }
}