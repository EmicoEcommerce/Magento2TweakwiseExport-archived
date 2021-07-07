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
    public const PATH_ENABLED = 'tweakwise/export/enabled';
    public const PATH_REAL_TIME = 'tweakwise/export/real_time';
    public const PATH_VALIDATE = 'tweakwise/export/validate';
    public const PATH_API_IMPORT_URL = 'tweakwise/export/api_import_url';
    public const PATH_STOCK_CALCULATION = 'tweakwise/export/stock_calculation';
    public const PATH_STOCK_PERCENTAGE = 'tweakwise/export/stock_percentage';
    public const PATH_OUT_OF_STOCK_CHILDREN = 'tweakwise/export/out_of_stock_children';
    public const PATH_FEED_KEY = 'tweakwise/export/feed_key';
    public const PATH_ALLOW_CACHE_FLUSH = 'tweakwise/export/allow_cache_flush';
    public const PATH_PRICE_FIELD = 'tweakwise/export/price_field';
    public const PATH_EXCLUDE_CHILD_ATTRIBUTES = 'tweakwise/export/exclude_child_attributes';
    public const BATCH_SIZE_CATEGORIES = 'tweakwise/export/batch_size_categories';
    public const BATCH_SIZE_PRODUCTS = 'tweakwise/export/batch_size_products';
    public const BATCH_SIZE_PRODUCTS_CHILDREN = 'tweakwise/export/batch_size_products_children';

    /**
     * default feed filename
     */
    public const FEED_FILE_NAME = 'tweakwise.xml';

    /**
     * @var ScopeConfigInterface
     */
    protected $config;

    /**
     * @var DirectoryList
     */
    protected $directoryList;

    /**
     * @var array
     */
    protected $skipAttributes;

    /**
     * @var DeploymentConfig
     */
    protected $deployConfig;

    /**
     * Export constructor.
     *
     * @param ScopeConfigInterface $config
     * @param DirectoryList $directoryList
     * @param DeploymentConfig $deployConfig
     */
    public function __construct(
        ScopeConfigInterface $config,
        DirectoryList $directoryList,
        DeploymentConfig $deployConfig
    ) {
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
     * @return bool
     */
    public function isOutOfStockChildren($store = null): bool
    {
        return (bool) $this->config->isSetFlag(self::PATH_OUT_OF_STOCK_CHILDREN, ScopeInterface::SCOPE_STORE, $store);
    }

    /**
     * @return string|null
     */
    public function getKey(): ?string
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

    /**
     * @return int
     */
    public function getBatchSizeCategories(): int
    {
        return (int) $this->config->getValue(self::BATCH_SIZE_CATEGORIES);
    }

    /**
     * @return int
     */
    public function getBatchSizeProducts(): int
    {
        return (int) $this->config->getValue(self::BATCH_SIZE_PRODUCTS);
    }

    /**
     * @return int
     */
    public function getBatchSizeProductsChildren(): int
    {
        return (int) $this->config->getValue(self::BATCH_SIZE_PRODUCTS_CHILDREN);
    }
}
