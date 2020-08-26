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
use Magento\Store\Model\Store;

class ExportEntity
{
    /**
     * @var Store
     */
    protected $store;

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
    protected $linkedWebsiteIds = [];

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
     * @param Store $store
     * @param StoreManagerInterface $storeManager
     * @param StockConfigurationInterface $stockConfiguration
     * @param Visibility $visibility
     * @param array $data
     * @internal param int $storeId
     */
    public function __construct(
        Store $store,
        StoreManagerInterface $storeManager,
        StockConfigurationInterface $stockConfiguration,
        Visibility $visibility,
        array $data = []
    ) {
        $this->setFromArray($data);
        $this->visibilityObject = $visibility;
        $this->store = $store;
        $this->stockConfiguration = $stockConfiguration;
        $this->storeManager = $storeManager;
    }

    /**
     * @param array $data
     */
    public function setFromArray(array $data): void
    {
        foreach ($data as $key => $value) {
            switch ($key) {
                case 'entity_id';
                    $this->id = (int) $value;
                    break;
                case 'type_id';
                    $this->setTypeId((string) $value);
                    $this->addAttribute($key, (string) $value);
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
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Store
     */
    public function getStore(): Store
    {
        return $this->store;
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
     */
    public function setStatus(int $status): void
    {
        $this->status = $status;
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
     */
    public function setVisibility(int $visibility): void
    {
        $this->visibility = $visibility;
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
     */
    public function setName(string $name): void
    {
        $this->name = $name;
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
     */
    public function setPrice(float $price): void
    {
        $this->price = $price;
    }

    /**
     * @return float
     */
    public function getStockQty(): float
    {
        return (float) ($this->getStockItem() ? $this->getStockItem()->getQty() : 0);
    }

    /**
     * @param int $id
     */
    public function addCategoryId(int $id): void
    {
        $this->categories[] = $id;
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
    public function addAttribute(string $attribute, $value): void
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
    public function setTypeId(string $typeId): void
    {
        $this->typeId = $typeId;
    }

    /**
     * @return string
     */
    public function getTypeId(): string
    {
        return $this->typeId;
    }

    /**
     * @return StockItem
     */
    public function getStockItem(): ?StockItem
    {
        return $this->stockItem;
    }

    /**
     * @param StockItem $stockItem
     */
    public function setStockItem($stockItem): void
    {
        $this->stockItem = $stockItem;
    }

    /**
     * @param int $id
     */
    public function addLinkedWebsiteId(int $id): void
    {
        $this->linkedWebsiteIds[] = $id;
    }

    /**
     * @return int[]
     */
    public function getLinkedWebsiteIds(): ?array
    {
        return $this->linkedWebsiteIds;
    }

    /**
     * @return bool
     */
    public function shouldProcess(): bool
    {
        return $this->shouldExportByStatus()
            && $this->shouldExportByVisibility()
            && $this->shouldExportByNameAttribute();
    }

    /**
     * @return bool
     */
    public function shouldExport(): bool
    {
        return $this->shouldProcess()
            && $this->shouldExportByWebsite()
            && $this->shouldExportByVisibility()
            && $this->shouldExportByStock();
    }

    /**
     * @return bool
     */
    public function shouldExportByStatus(): bool
    {
        return $this->getStatus() === Status::STATUS_ENABLED;
    }

    /**
     * @return bool
     */
    protected function shouldExportByWebsite(): bool
    {
        if ($this->storeManager->isSingleStoreMode()) {
            return true;
        }

        $websiteId = (int) $this->store->getWebsiteId();
        return \in_array($websiteId, $this->linkedWebsiteIds, true);
    }

    /**
     * @return bool
     */
    protected function shouldExportByVisibility(): bool
    {
        return \in_array($this->getVisibility(), $this->visibilityObject->getVisibleInSiteIds(), true);

    }

    /**
     * @return bool
     */
    protected function shouldExportByStock(): bool
    {
        if ($this->stockConfiguration->isShowOutOfStock($this->store->getId())) {
            return true;
        }

        return $this->getStockItem() ? (bool) $this->getStockItem()->getIsInStock() : false;
    }

    /**
     * @return bool
     */
    protected function shouldExportByNameAttribute(): bool
    {
        return !empty($this->getName());
    }
}
