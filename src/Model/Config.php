<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;

class Config
{
    /**
     * default feed filename
     */
    const FEED_FILE_NAME = 'tweakwise.xml';

    /**
     * @var ScopeConfigInterface
     */
    protected $config;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * Export constructor.
     *
     * @param ScopeConfigInterface $config
     * @param DirectoryList $directoryList
     */
    public function __construct(ScopeConfigInterface $config, DirectoryList $directoryList)
    {
        $this->config = $config;
        $this->directoryList = $directoryList;
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {
        return (bool) $this->config->getValue('tweakwise/export/enabled');
    }

    /**
     * @return bool
     */
    public function isRealTime()
    {
        return (bool) $this->config->getValue('tweakwise/export/real_time');
    }

    /**
     * @return bool
     */
    public function isValidate()
    {
        return (bool) $this->config->getValue('tweakwise/export/validate');
    }

    /**
     * @return string
     */
    public function getApiServerUrl()
    {
        return (string) $this->config->getValue('tweakwise/export/api_server_url');
    }

    /**
     * @return string
     */
    public function getApiImportKey()
    {
        return (string) $this->config->getValue('tweakwise/export/api_import_key');
    }

    /**
     * @return string
     */
    public function getStockCalculation()
    {
        return (string) $this->config->getValue('tweakwise/export/stock_calculation');
    }

    /**
     * @return int
     */
    public function getProductSelection()
    {
        return (int) $this->config->getValue('tweakwise/export/product_selection');
    }

    /**
     * @return bool
     */
    public function isOutOfStockChildren()
    {
        return (bool) $this->config->getValue('tweakwise/export/out_of_stock_children');
    }

    /**
     * @return string
     */
    public function getDefaultFeedPath()
    {
        $dir = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR . 'feeds';
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        return $dir . DIRECTORY_SEPARATOR . self::FEED_FILE_NAME;
    }
}
