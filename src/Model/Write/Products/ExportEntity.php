<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use Emico\TweakwiseExport\Exception\InvalidArgumentException;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Visibility;

class ExportEntity
{
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
     * @var ExportEntityChild[]
     */
    private $children = [];

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
    private $isComposite = null;

    /**
     * @var float
     */
    private $stockQty = 0.0;

    /**
     * ExportEntity constructor.
     *
     * @param Visibility $visibility
     * @param array $data
     */
    public function __construct(Visibility $visibility, array $data = [])
    {
        $this->setFromArray($data);
        $this->visibilityObject = $visibility;
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
                    break;
                case 'visibility';
                    $this->setVisibility((int) $value);
                    break;
                case 'name';
                    $this->setName((string) $value);
                    break;
                case 'qty';
                    $this->setStockQty((float) $value);
                    break;
                case 'price';
                    $this->setPrice((float) $value);
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
        return $this->stockQty;
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
    public function shouldExport(): bool
    {
        return $this->shouldExportByStatus() &&
            $this->shouldExportByVisibility() &&
            $this->shouldExportByStock();
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
     * @param ExportEntityChild $child
     * @return $this
     */
    public function addChild(ExportEntityChild $child)
    {
        $this->children[$child->getId()] = $child;
        return $this;
    }

    /**
     * @param ExportEntityChild $child
     * @return $this
     */
    public function removeChild(ExportEntityChild $child)
    {
        if (!isset($this->children[$child->getId()])) {
            throw new InvalidArgumentException(sprintf('Child %s not set on %s', $child->getId(), $this->getId()));
        }

        unset($this->children[$child->getId()]);
        return $this;
    }

    /**
     * @return ExportEntityChild[]
     */
    public function getChildren(): array
    {
        return $this->children;
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
        return true;
    }
}