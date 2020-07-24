<?php

/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2020.
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

interface CompositeExportEntityInterface
{
    /**
     * @param ExportEntityChild $child
     * @return mixed
     */
    public function addChild(ExportEntityChild $child);

    /**
     * This method should return all known children for the composite export entity
     *
     * @return ExportEntityChild[]
     */
    public function getAllChildren(): array;

    /**
     * This method should return all children that are eligible for export (this will be written in the feed)
     *
     * @return ExportEntityChild[]
     */
    public function getExportChildren(): array;

    /**
     * This method should return all children that are eligible for export except for maybe they are out of stock
     * This is used to keep track of stock status on the product, stock status may depend on the children that are out of stock
     *
     * @return ExportEntityChild[]
     */
    public function getExportChildrenIncludeOutOfStock(): array;
}
