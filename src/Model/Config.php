<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\DeploymentConfig;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Store\Model\Store;

class Config
{
    /**
     * default feed filename
     */
    const FEED_FILE_NAME = 'tweakwise.xml';

    /**
     * @var ScopeConfigInterface
     */
    private $config;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var array
     */
    private $skipAttributes;

    /**
     * @var DeploymentConfig
     */
    private $deployConfig;

    /**
     * Export constructor.
     *
     * @param ScopeConfigInterface $config
     * @param DirectoryList $directoryList
     * @param DeploymentConfig $deployConfig
     */
    public function __construct(ScopeConfigInterface $config, DirectoryList $directoryList, DeploymentConfig $deployConfig)
    {
        $this->config = $config;
        $this->directoryList = $directoryList;
        $this->deployConfig = $deployConfig;
    }

    /**
     * @param Store $store
     * @return bool
     */
    public function isEnabled(Store $store = null)
    {
        if ($store) {
            return $store->getConfig('tweakwise/export/enabled');
        }
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
        if (!$this->deployConfig->isAvailable()) {
            return false;
        }

        return (bool) $this->config->getValue('tweakwise/export/validate');
    }

    /**
     * @return string
     */
    public function getApiImportUrl()
    {
        return (string) $this->config->getValue('tweakwise/export/api_import_url');
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
    public function getKey()
    {
        return (string) $this->config->getValue('tweakwise/export/feed_key');
    }

    /**
     * @return string[]
     */
    public function getPriceFields()
    {
        $data = (array) explode(',', $this->config->getValue('tweakwise/export/price_field'));
        return array_filter($data);
    }

    /**
     * @param string|null $attribute
     * @return bool|string[]
     */
    public function getSkipAttribute($attribute = null)
    {
        if (!$this->skipAttributes) {
            $skipAttributes = explode(',', $this->config->getValue('tweakwise/export/exclude_child_attributes'));
            $this->skipAttributes = array_flip($skipAttributes);
        }

        if ($attribute === null) {
            return array_keys($this->skipAttributes);
        }

        return isset($this->skipAttributes[$attribute]);
    }

    /**
     * @return string
     */
    public function getDefaultFeedFile()
    {
        $dir = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR . 'feeds';
        if (!is_dir($dir)) {
            mkdir($dir);
        }

        return $dir . DIRECTORY_SEPARATOR . self::FEED_FILE_NAME;
    }

    /**
     * @param string|null $file
     * @return string
     */
    public function getFeedLockFile($file = null)
    {
        if (!$file) {
            $file = $this->getDefaultFeedFile();
        }

        return $file . '.lock';
    }

    /**
     * @param string|null $file
     * @return string
     */
    public function getFeedTmpFile($file = null)
    {
        if (!$file) {
            $file = $this->getDefaultFeedFile();
        }

        return $file . '.tmp';
    }
}
