<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Helper;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Db_Statement_Exception;

class WebsiteLink extends AbstractDecorator
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * WebsiteLink constructor.
     *
     * @param DbContext $context
     * @param StoreManagerInterface $storeManager
     * @param Helper $helper
     */
    public function __construct(DbContext $context, StoreManagerInterface $storeManager, Helper $helper)
    {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->helper = $helper;
    }

    /**
     * Decorate items with extra data or remove items completely
     *
     * @param Collection $collection
     * @throws Zend_Db_Statement_Exception
     */
    public function decorate(Collection $collection)
    {
        if ($this->storeManager->isSingleStoreMode()) {
            return;
        }

        $this->addLinkedWebsiteIds($collection);
        $this->ensureWebsiteLinkedSet($collection);
    }

    /**
     * @return string
     */
    private function getProductWebsiteTable(): string
    {
        return $this->getResources()->getTableName('catalog_product_website');
    }

    /**
     * @param Collection $collection
     */
    private function addLinkedWebsiteIds(Collection $collection)
    {
        if ($this->helper->isEnterprise()) {
            $this->addLinkedWebsiteIdsEnterprise($collection);
        } else {
            $this->addLinkedWebsiteIdsCommunity($collection);
        }
    }

    /**
     * @param Collection $collection
     */
    private function addLinkedWebsiteIdsEnterprise(Collection $collection)
    {
        $entityRowIdMap = $this->getEntityIdRowIdMap($collection->getIds());
        $entityIds = array_keys($entityRowIdMap);
        $select = $this->getConnection()->select()
            ->from($this->getProductWebsiteTable(), ['product_id', 'website_id'])
            ->where('product_id IN (?)', $entityIds);
        $query = $select->query();

        while ($row = $query->fetch()) {
            $productId = (int)$row['product_id'];
            $collection->get($entityRowIdMap[$productId])->addLinkedWebsiteId((int)$row['website_id']);
        }
    }

    /**
     * @param Collection $collection
     */
    private function addLinkedWebsiteIdsCommunity(Collection $collection)
    {
        $select = $this->getConnection()->select()
            ->from($this->getProductWebsiteTable(), ['product_id', 'website_id'])
            ->where('product_id IN (?)', $collection->getIds());
        $query = $select->query();

        while ($row = $query->fetch()) {
            $productId = (int)$row['product_id'];
            $collection->get($productId)->addLinkedWebsiteId((int)$row['website_id']);
        }
    }

    /**
     * @param Collection $collection
     */
    private function ensureWebsiteLinkedSet(Collection $collection)
    {
        /** @var ExportEntity $entity */
        foreach ($collection as $entity) {
            $entity->ensureWebsiteLinkedIdsSet();
        }
    }
}