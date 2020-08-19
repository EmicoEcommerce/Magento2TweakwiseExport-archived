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
    protected $enabledChildren;

    /**
     * @var ExportEntityChild[]
     */
    protected $exportableChildren;

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
        if ($this->exportableChildren !== null) {
            return $this->exportableChildren;
        }

        $this->exportableChildren = [];

        foreach ($this->getAllChildren() as $child) {
            if ($child->shouldExport()) {
                $this->exportableChildren[] = $child;
            }
        }

        return $this->exportableChildren;
    }

    /**
     * This method should return all enabled export children regardless of stock status and quantity
     *
     * @return ExportEntityChild[]
     */
    public function getEnabledChildren(): array
    {
        if ($this->enabledChildren !== null) {
            return $this->enabledChildren;
        }

        $this->enabledChildren = [];

        foreach ($this->getAllChildren() as $child) {
            if ($child->shouldExportByStatus()) {
                $this->enabledChildren[] = $child;
            }
        }

        return $this->enabledChildren;
    }

    /**
     * @return bool
     */
    public function shouldExport(): bool
    {
        return $this->shouldExportByChildStatus() && parent::shouldExport();
    }

    /**
     * @return bool
     */
    protected function shouldExportByChildStatus(): bool
    {
        return count($this->getEnabledChildren()) > 0;
    }
}
