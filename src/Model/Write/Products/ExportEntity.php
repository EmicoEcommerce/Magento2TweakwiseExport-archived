<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use Emico\TweakwiseExport\Exception\InvalidArgumentException;
use Emico\TweakwiseExport\Model\StockItem;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Store\Model\StoreManagerInterface;

class ExportEntity
{
    /**
     * @var int
     */
    protected $storeId;

    /**
     * @var Visibility
     */
    protected $visibilityObject;

    /**
     * @var int[]
     */
    protected $categories = [];

    /**
     * @var array[]
     */
    protected $attributes = [];

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $status;

    /**
     * @var int
     */
    protected $visibility;

    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var float
     */
    protected $price = 0.0;

    /**
     * @var StockItem
     */
    protected $stockItem;

    /**
     * @var StockConfigurationInterface
     */
    protected $stockConfiguration;

    /**
     * @var int[]|null
     */
    protected $linkedWebsiteIds;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var string
     */
    protected $typeId;

    /**
     * ExportEntity constructor.
     *
     * @param int $storeId
     * @param StoreManagerInterface $storeManager
     * @param StockConfigurationInterface $stockConfiguration
     * @param Visibility $visibility
     * @param array $data
     * @internal param int $storeId
     */
    public function __construct(
        int $storeId,
        StoreManagerInterface $storeManager,
        StockConfigurationInterface $stockConfiguration,
        Visibility $visibility,
        array $data = []
    ) {
        $this->setFromArray($data);
        $this->visibilityObject = $visibility;
        $this->storeId = $storeId;
        $this->stockConfiguration = $stockConfiguration;
        $this->storeManager = $storeManager;
    }

    /**
     * {@inheritdoc}
     */
    public function setFromArray(array $data)
    {
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'entity_id';
                    $this->id = (int) $value;
                    break;
                case 'status';
                    $this->setStatus((int) $value);
                    $this->addAttribute($key, (int) $value);
                    break;
                case 'visibility';
                    $this->setVisibility((int) $value);
                    $this->addAttribute($key, (int) $value);
                    break;
                case 'name';
                    $this->setName((string) $value);
                    $this->addAttribute($key, (string) $value);
                    break;
                case 'price';
                    $this->setPrice((float) $value);
                    $this->addAttribute($key, (float) $value);
                    break;
                default:
                    $this->addAttribute($key, $value);
                    break;
            }
        }

        return $this;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @return int
     */
    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * @param int $status
     * @return $this
     */
    public function setStatus(int $status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return int
     */
    public function getVisibility(): int
    {
        return $this->visibility;
    }

    /**
     * @param int $visibility
     * @return $this
     */
    public function setVisibility(int $visibility)
    {
        $this->visibility = $visibility;
        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }

    /**
     * @param float $price
     * @return $this
     */
    public function setPrice(float $price)
    {
        $this->price = $price;
        return $this;
    }

    /**
     * @return bool
     */
    public function isComposite(): bool
    {
        return $this instanceof CompositeExportEntityInterface;
    }

    /**
     * @return float
     */
    public function getStockQty(): float
    {
        return (float) $this->stockItem->getQty();
    }

    /**
     * @return bool
     */
    public function shouldExport(): bool
    {
        return $this->shouldExportByStock() && $this->shouldExportAllowOutOfStock();
    }

    /**
     * @return bool
     */
    protected function shouldExportAllowOutOfStock(): bool
    {
        return $this->shouldExportByStatus() &&
            $this->shouldExportByWebsite() &&
            $this->shouldExportByVisibility() &&
            $this->shouldExportByNameAttribute();
    }

    /**
     * @param int $id
     * @return $this
     */
    public function addCategoryId(int $id)
    {
        $this->categories[] = $id;
        return $this;
    }

    /**
     * @return int[]
     */
    public function getCategories(): array
    {
        return $this->categories;
    }

    /**
     * @param string $attribute
     * @param $value
     */
    public function addAttribute(string $attribute, $value)
    {
        if (!isset($this->attributes[$attribute])) {
            $this->attributes[$attribute] = [];
        }

        $this->attributes[$attribute][] = $value;
    }

    /**
     * @return array[]
     */
    public function getAttributes(): array
    {
        $result = [];
        foreach ($this->attributes as $attribute => $values) {
            foreach ($values as $value) {
                $result[$attribute . $value] = ['attribute' => $attribute, 'value' => $value];
            }
        }

        return array_values($result);
    }

    /**
     * @param string $attribute
     * @param bool $asArray
     * @return array|mixed
     * @throws InvalidArgumentException
     */
    public function getAttribute(string $attribute, bool $asArray = true)
    {
        if (!isset($this->attributes[$attribute])) {
            throw new InvalidArgumentException(sprintf('Could not find attribute %s', $attribute));
        }

        if ($asArray || count($this->attributes[$attribute]) > 1) {
            return $this->attributes[$attribute];
        }

        return current($this->attributes[$attribute]);
    }

    /**
     * @param string $typeId
     */
    public function setTypeId(string $typeId)
    {
        $this->typeId = $typeId;
    }

    /**
     * @return string
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * @return StockItem
     */
    public function getStockItem()
    {
        return $this->stockItem;
    }

    /**
     * @param StockItem $stockItem
     * @return $this
     */
    public function setStockItem($stockItem)
    {
        $this->stockItem = $stockItem;
        return $this;
    }

    /**
     * @param int $id
     */
    public function addLinkedWebsiteId(int $id)
    {
        $this->ensureWebsiteLinkedIdsSet();
        $this->linkedWebsiteIds[] = $id;
    }

    /**
     * Ensure linked website ids is no longer NULL
     */
    public function ensureWebsiteLinkedIdsSet()
    {
        if ($this->linkedWebsiteIds === null) {
            $this->linkedWebsiteIds = [];
        }
    }

    /**
     * @return int[]|null
     */
    public function getLinkedWebsiteIds()
    {
        return $this->linkedWebsiteIds;
    }

    /**
     * @return bool
     */
    protected function shouldExportByStatus(): bool
    {
        if ($this->status === null) {
            return true;
        }

        return $this->getStatus() === Status::STATUS_ENABLED;
    }

    /**
     * @return bool
     */
    protected function shouldExportByWebsite(): bool
    {
        if ($this->linkedWebsiteIds === null) {
            return true;
        }

        if ($this->storeManager->isSingleStoreMode()) {
            return true;
        }

        $websiteId = (int) $this->storeManager->getStore($this->storeId)->getWebsiteId();
        return \in_array($websiteId, $this->linkedWebsiteIds, true);
    }

    /**
     * @return bool
     */
    protected function shouldExportByVisibility(): bool
    {
        if ($this->visibility === null) {
            return true;
        }

        return \in_array($this->getVisibility(), $this->visibilityObject->getVisibleInSiteIds(), true);

    }

    /**
     * @return bool
     */
    protected function shouldExportByStock(): bool
    {
        if ($this->stockItem === null) {
            return true;
        }

        if ($this->stockConfiguration->isShowOutOfStock($this->storeId)) {
            return true;
        }

        return $this->stockItem->getIsInStock();
    }

    /**
     * @return bool
     */
    protected function shouldExportByNameAttribute(): bool
    {
        return !empty($this->getName());
    }
}
