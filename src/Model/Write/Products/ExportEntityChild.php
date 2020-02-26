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
     * @var bool
     */
    protected $isRequired = false;

    /**
     * @return bool
     */
    public function isRequired()
    {
        return $this->isRequired;
    }

    /**
     * @param bool $required
     */
    public function setRequired(bool $required)
    {
        $this->isRequired = $required;
    }

    /**
     * ExportEntityChild constructor.
     * @param int $storeId
     * @param StoreManagerInterface $storeManager
     * @param StockConfigurationInterface $stockConfiguration
     * @param Config $config
     * @param Visibility $visibility
     * @param array $data
     */
    public function __construct(
        int $storeId,
        StoreManagerInterface $storeManager,
        StockConfigurationInterface $stockConfiguration,
        Config $config,
        Visibility $visibility,
        array $data = []
    ) {
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
     * @param bool $includeOutOfStock
     * @return bool
     */
    public function shouldExport($includeOutOfStock = false): bool
    {
        if ($this->config->isOutOfStockChildren($this->storeId)) {
            $includeOutOfStock = true;
        }

        return parent::shouldExport($includeOutOfStock);
    }
}
