<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use Emico\TweakwiseExport\Model\Config;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Store\Model\StoreManagerInterface;

class ExportEntityChild extends ExportEntity
{
    /**
     * @var Config
     */
    private $config;

    /**
     * ExportEntityChild constructor.
     * @param int $storeId
     * @param StoreManagerInterface $storeManager
     * @param StockConfigurationInterface $stockConfiguration
     * @param Config $config
     * @param Visibility $visibility
     * @param array $data
     */
    public function __construct(int $storeId, StoreManagerInterface $storeManager, StockConfigurationInterface $stockConfiguration, Config $config, Visibility $visibility, array $data = [])
    {
        parent::__construct($storeId, $storeManager, $stockConfiguration, $visibility, $data);
        $this->config = $config;
    }

    /**
     * @return bool
     */
    protected function shouldExportByVisibility(): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    protected function shouldExportByStock(): bool
    {
        if ($this->config->isOutOfStockChildren($this->storeId)) {
            return true;
        }

        return parent::shouldExportByStock();
    }
}