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
    private $visibilityObject;

    /**
     * @var int[]
     */
    private $categories = [];

    /**
     * @var array[]
     */
    private $attributes = [];

    /**
     * @var ExportEntityChild[]|null
     */
    private $children;

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $status;

    /**
     * @var int
     */
    private $visibility;

    /**
     * @var string
     */
    private $name = '';

    /**
     * @var float
     */
    private $price = 0.0;

    /**
     * @var bool|null
     */
    private $isComposite;

    /**
     * @var StockItem
     */
    private $stockItem;

    /**
     * @var StockConfigurationInterface
     */
    private $stockConfiguration;

    /**
     * @var int[]|null
     */
    private $linkedWebsiteIds;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

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
    public function __construct(int $storeId, StoreManagerInterface $storeManager, StockConfigurationInterface $stockConfiguration, Visibility $visibility, array $data = [])
    {
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
     * @return float
     */
    public function getStockQty(): float
    {
        return (float) $this->stockItem->getQty();
    }

    /**
     * @param float $stockQty
     * @return $this
     */
    public function setStockQty(float $stockQty)
    {
        $this->stockQty = $stockQty;
        return $this;
    }

    /**
     * @return bool
     */
    public function shouldExport($includeOutOfStock = false): bool
    {
        $shouldExport = $this->shouldExportByStatus() &&
            $this->shouldExportByWebsite() &&
            $this->shouldExportByVisibility() &&
            $this->shouldExportByComposite() &&
            $this->shouldExportByNameAttribute();

        if (!$includeOutOfStock) {
            $shouldExport = $shouldExport && $this->shouldExportByStock();
        }

        return $shouldExport;
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
     * @param ExportEntityChild[] $children
     * @return $this
     */
    public function setChildren(array $children)
    {
        foreach ($children as $child) {
            $this->addChild($child);
        }
        return $this;
    }

    /**
     * @param ExportEntityChild $child
     * @return $this
     */
    public function addChild(ExportEntityChild $child)
    {
        if ($this->children === null) {
            $this->children = [];
        }

        $this->children[$child->getId()] = $child;
        return $this;
    }

    /**
     * @param ExportEntityChild $child
     * @return $this
     */
    public function removeChild(ExportEntityChild $child)
    {
        if ($this->children === null || !isset($this->children[$child->getId()])) {
            throw new InvalidArgumentException(sprintf('Child %s not set on %s', $child->getId(), $this->getId()));
        }

        unset($this->children[$child->getId()]);
        return $this;
    }

    /**
     * @return ExportEntityChild[]
     */
    public function getExportChildren(): array
    {
        if ($this->children === null) {
            return [];
        }

        $result = [];
        foreach ($this->children as $child) {
            if (!$child->shouldExport()) {
                continue;
            }

            $result[$child->getId()] = $child;
        }

        return $result;
    }

    /**
     * @return ExportEntityChild[]
     */
    public function getExportChildrenIncludeOutOfStock(): array
    {
        if ($this->children === null) {
            return [];
        }

        $children = [];
        foreach ($this->children as $child) {
            if ($child->shouldExport(true)) {
                $children[] = $child;
            }
        }

        return $children;
    }

    /**
     * @param bool $isComposite
     * @return $this
     */
    public function setIsComposite(bool $isComposite)
    {
        $this->isComposite = $isComposite;
        return $this;
    }

    /**
     * @return bool|null
     */
    public function isComposite()
    {
        return $this->isComposite;
    }

    /**
     * @return null|StockItem
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
    protected function shouldExportByComposite(): bool
    {
        if ($this->isComposite === null || $this->isComposite === false) {
            return true;
        }

        if ($this->children === null) {
            return true;
        }

        return count($this->getExportChildren()) > 0;
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
        return !empty($this->attributes['name']);
    }
}
