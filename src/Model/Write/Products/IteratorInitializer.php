<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use Emico\TweakwiseExport\Model\ProductAttributes;
use Emico\TweakwiseExport\Model\Write\EavIterator;

/**
 * Class IteratorInitializer
 * @package Emico\TweakwiseExport\Model\Write\Products
 */
class IteratorInitializer
{
    /**
     * @var ProductAttributes
     */
    private $productAttributes;

    /**
     * IteratorInitializer constructor.
     *
     * @param ProductAttributes $productAttributes
     */
    public function __construct(ProductAttributes $productAttributes)
    {
        $this->productAttributes = $productAttributes;
    }

    /**
     * Select all attributes who should be exported
     *
     * @param EavIterator $iterator
     */
    public function initializeAttributes(EavIterator $iterator)
    {
        // Add default attributes
        $iterator->selectAttribute('name');
        $iterator->selectAttribute('sku');
        $iterator->selectAttribute('url_key');
        $iterator->selectAttribute('status');
        $iterator->selectAttribute('visibility');
        $iterator->selectAttribute('type_id');

        foreach ($this->productAttributes->getAttributesToExport() as $attribute) {
            $iterator->selectAttribute($attribute->getAttributeCode());
        }
    }
}
