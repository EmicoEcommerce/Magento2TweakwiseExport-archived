<?php

/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2020.
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

/**
 * Class CompositeExportEntity
 * @package Emico\TweakwiseExport\Model\Write\Products
 */
class CompositeExportEntity extends ExportEntity implements CompositeExportEntityInterface
{
    /**
     * @var ExportEntityChild[]
     */
    protected $children = [];

    /**
     * @var ExportEntityChild[]
     */
    protected $exportableChildren = [];

    /**
     * @var ExportEntityChild[]
     */
    protected $exportableChildrenIncludingOutOfStock = [];

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
     * @return ExportEntityChild[]
     */
    public function getAllChildren(): array
    {
        return $this->children;
    }

    /**
     * This method should return all children that are eligible for export (this will be written in the feed)
     *
     * @return ExportEntity[]
     */
    public function getExportChildren(): array
    {
        if ($this->exportableChildren) {
            return $this->exportableChildren;
        }

        $this->exportableChildren = array_filter(
            $this->children,
            [$this, 'shouldExport']
        );

        return $this->exportableChildren;
    }

    /**
     * @return array|ExportEntityChild[]
     */
    public function getExportChildrenIncludeOutOfStock(): array
    {
        if ($this->exportableChildrenIncludingOutOfStock) {
            return $this->exportableChildrenIncludingOutOfStock;
        }

        $this->exportableChildrenIncludingOutOfStock = array_filter(
            $this->children,
            [$this, 'shouldExportAllowOutOfStock']
        );

        return $this->exportableChildrenIncludingOutOfStock;
    }

    /**
     * @return bool
     */
    public function shouldExport(): bool
    {
        return $this->shouldExportByComposite() && parent::shouldExport();
    }

    /**
     * @return bool
     */
    public function shouldExportAllowOutOfStock(): bool
    {
        return $this->shouldExportByComposite() && parent::shouldExportAllowOutOfStock();
    }

    /**
     * @return bool
     */
    protected function shouldExportByComposite(): bool
    {
        if ($this->children === null) {
            return true;
        }

        return count($this->getExportChildren()) > 0;
    }
}
