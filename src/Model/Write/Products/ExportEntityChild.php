<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use Emico\TweakwiseExport\Model\Config;
use Magento\Catalog\Model\Product\Visibility;

class ExportEntityChild extends ExportEntity
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var int
     */
    private $storeId;

    /**
     * ExportEntityChild constructor.
     * @param int $storeId
     * @param Config $config
     * @param Visibility $visibility
     * @param array $data
     */
    public function __construct(int $storeId, Config $config, Visibility $visibility, array $data = [])
    {
        parent::__construct($visibility, $data);
        $this->config = $config;
        $this->storeId = $storeId;
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