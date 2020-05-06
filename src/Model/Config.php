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
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use RuntimeException;

class Config
{
    /**
     * Config path constants
     */
    const PATH_ENABLED = 'tweakwise/export/enabled';
    const PATH_REAL_TIME = 'tweakwise/export/real_time';
    const PATH_VALIDATE = 'tweakwise/export/validate';
    const PATH_API_IMPORT_URL = 'tweakwise/export/api_import_url';
    const PATH_STOCK_CALCULATION = 'tweakwise/export/stock_calculation';
    const PATH_STOCK_PERCENTAGE = 'tweakwise/export/stock_percentage';
    const PATH_OUT_OF_STOCK_CHILDREN = 'tweakwise/export/out_of_stock_children';
    const PATH_FEED_KEY = 'tweakwise/export/feed_key';
    const PATH_ALLOW_CACHE_FLUSH = 'tweakwise/export/allow_cache_flush';
    const PATH_PRICE_FIELD = 'tweakwise/export/price_field';
    const PATH_EXCLUDE_CHILD_ATTRIBUTES = 'tweakwise/export/exclude_child_attributes';

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
     * @param Store|int|string|null $store
     * @return bool
     */
    public function isEnabled($store = null): bool
    {
        return (bool) $this->config->isSetFlag(self::PATH_ENABLED, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @return bool
     */
    public function isRealTime(): bool
    {
        return (bool) $this->config->isSetFlag(self::PATH_REAL_TIME);
    }

    /**
     * @return bool
     */
    public function isValidate(): bool
    {
        if (!$this->deployConfig->isAvailable()) {
            return false;
        }

        return (bool) $this->config->isSetFlag(self::PATH_VALIDATE);
    }

    /**
     * @return string
     */
    public function getApiImportUrl(): string
    {
        return (string) $this->config->getValue(self::PATH_API_IMPORT_URL);
    }

    /**
     * @param Store|int|string|null $store
     * @return string
     */
    public function getStockCalculation($store = null): string
    {
        return (string) $this->config->getValue(self::PATH_STOCK_CALCULATION, ScopeInterface::SCOPE_STORE, $store);
    }


    /**
     * @param Store|int|string|null $store
     * @return bool
     */
    public function isOutOfStockChildren($store = null): bool
    {
        return (bool) $this->config->isSetFlag(self::PATH_OUT_OF_STOCK_CHILDREN, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @return string|null
     */
    public function getKey()
    {
        return $this->config->getValue(self::PATH_FEED_KEY);
    }

    /**
     * @return bool Allow cache flush or not
     */
    public function isAllowCacheFlush(): bool
    {
        return (bool) $this->config->getValue(self::PATH_ALLOW_CACHE_FLUSH);
    }

    /**
     * @param Store|int|string|null $store
     * @return string[]
     */
    public function getPriceFields($store = null): array
    {
        $data = (array) explode(',', $this->config->getValue(self::PATH_PRICE_FIELD, ScopeInterface::SCOPE_STORE, $store));
        return array_filter($data);
    }

    /**
     * @param Store|int|string|null $store
     * @param string|null $attribute
     * @return bool|string[]
     */
    public function getSkipChildAttribute($attribute = null, $store = null)
    {
        if (!$this->skipAttributes) {
            $value = $this->config->getValue(self::PATH_EXCLUDE_CHILD_ATTRIBUTES, ScopeInterface::SCOPE_STORE, $store);
            $skipAttributes = explode(',', $value);
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
    public function getDefaultFeedFile(): string
    {
        $dir = $this->directoryList->getPath('var') . DIRECTORY_SEPARATOR . 'feeds';
        if (!is_dir($dir) && !mkdir($dir) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }

        return $dir . DIRECTORY_SEPARATOR . self::FEED_FILE_NAME;
    }

    /**
     * @param string|null $file
     * @return string
     */
    public function getFeedLockFile($file = null): string
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
    public function getFeedTmpFile($file = null): string
    {
        if (!$file) {
            $file = $this->getDefaultFeedFile();
        }

        return $file . '.tmp';
    }
}
