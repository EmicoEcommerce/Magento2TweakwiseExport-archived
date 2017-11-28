<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use Emico\TweakwiseExport\Exception\InvalidArgumentException;
use Generator;
use Magento\Catalog\Model\Product\Attribute\Source\Status;

class ExportEntity
{
    /**
     * @var int[]
     */
    protected $categories = [];

    /**
     * @var array[]
     */
    protected $attributes = [];

    /**
     * @var ExportEntity[]
     */
    protected $children = [];

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
     * @var bool
     */
    protected $isComposite = false;

    /**
     * @var float
     */
    protected $stockQty = 0.0;

    /**
     * ExportEntity constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->setFromArray($data);
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
        if ($this->getStatus() !== Status::STATUS_ENABLED) {
            return false;
        }

        return true;
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

        $this->attributes[$attribute][$value] = true;
    }

    /**
     * @return array[]
     */
    public function getAttributes(): array
    {
        $result = [];
        foreach ($this->attributes as $attribute => $values) {
            foreach ($values as $value => $junk) {
                $result[$attribute . $value] = ['attribute' => $attribute, 'value' => $value];
            }
        }

        $childrenAttributes = array_map(function(ExportEntity $child) { return $child->getAttributes(); }, $this->children);
        $result = array_merge($result, ...$childrenAttributes);

        return $result;
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
     * @param ExportEntity $child
     * @return $this
     */
    public function addChild(ExportEntity $child)
    {
        $this->children[$child->getId()] = $child;
        return $this;
    }

    /**
     * @return bool
     */
    public function isComposite(): bool
    {
        return $this->isComposite;
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
}