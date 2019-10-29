<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use Emico\TweakwiseExport\Model\Helper;
use Emico\TweakwiseExport\Model\Write\EavIterator;

class IteratorInitializer
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * IteratorInitializer constructor.
     *
     * @param Helper $helper
     */
    public function __construct(Helper $helper)
    {
        $this->helper = $helper;
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

        foreach ($this->helper->getAttributesToExport() as $attribute) {
            $iterator->selectAttribute($attribute->getAttributeCode());
        }
    }
}
