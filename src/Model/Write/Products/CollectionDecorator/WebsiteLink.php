<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\DbResourceHelper;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Magento\Store\Model\StoreManagerInterface;
use Zend_Db_Statement_Exception;

class WebsiteLink implements DecoratorInterface
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var DbResourceHelper
     */
    protected $dbResource;

    /**
     * WebsiteLink constructor.
     *
     * @param StoreManagerInterface $storeManager
     * @param DbResourceHelper $dbResource
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        DbResourceHelper $dbResource
    ) {
        $this->dbResource = $dbResource;
        $this->storeManager = $storeManager;
    }

    /**
     * Decorate items with extra data or remove items completely
     *
     * @param Collection $collection
     * @throws Zend_Db_Statement_Exception
     */
    public function decorate(Collection $collection): void
    {
        if ($this->storeManager->isSingleStoreMode()) {
            return;
        }

        $this->addLinkedWebsiteIds($collection);
    }

    /**
     * @return string
     */
    protected function getProductWebsiteTable(): string
    {
        return $this->dbResource->getTableName('catalog_product_website');
    }

    /**
     * @param Collection $collection
     * @throws Zend_Db_Statement_Exception
     */
    protected function addLinkedWebsiteIds(Collection $collection): void
    {
        $select = $this->dbResource->getConnection()->select()
            ->from($this->getProductWebsiteTable(), ['product_id', 'website_id'])
            ->where('product_id in(' . implode(',', $collection->getIds()) . ')');
        $query = $select->query();

        while ($row = $query->fetch()) {
            $productId = (int)$row['product_id'];
            $collection->get($productId)->addLinkedWebsiteId((int)$row['website_id']);
        }
    }
}
