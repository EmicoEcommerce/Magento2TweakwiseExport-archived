<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use Emico\TweakwiseExport\Model\ChildOptions;
use Emico\TweakwiseExport\Model\Config;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;

class ExportEntityChild extends ExportEntity
{
    /**
     * @var ChildOptions
     */
    protected ?ChildOptions $childOptions = null;

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * ExportEntityChild constructor.
     * @param Config $config
     * @param Store $store
     * @param StoreManagerInterface $storeManager
     * @param StockConfigurationInterface $stockConfiguration
     * @param Visibility $visibility
     * @param array $data
     */
    public function __construct(
        Config $config,
        Store $store,
        StoreManagerInterface $storeManager,
        StockConfigurationInterface $stockConfiguration,
        Visibility $visibility,
        array $data = []
    ) {
        parent::__construct(
            $store,
            $storeManager,
            $stockConfiguration,
            $visibility,
            $data
        );

        $this->config = $config;
    }

    /**
     * @return ChildOptions
     */
    public function getChildOptions(): ?ChildOptions
    {
        return $this->childOptions;
    }

    /**
     * @param ChildOptions $childOptions
     */
    public function setChildOptions(ChildOptions $childOptions): void
    {
        $this->childOptions = $childOptions;
    }

    /**
     * @return bool
     */
    public function shouldExport(): bool
    {
        return $this->shouldExportByStock()
            && $this->shouldExportByStatus();
    }

    /**
     * @return bool
     */
    public function shouldExportByStock(): bool
    {
        if ($this->config->isOutOfStockChildren()) {
            return true;
        }

        return $this->isInStock();
    }
}
