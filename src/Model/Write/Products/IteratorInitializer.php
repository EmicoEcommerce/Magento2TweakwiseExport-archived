<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products;


use Emico\TweakwiseExport\Model\Helper;
use Emico\TweakwiseExport\Model\Write\EavIterator;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Config;

class IteratorInitializer
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Helper
     */
    private $helper;

    /**
     * IteratorInitializer constructor.
     * @param Config $config
     * @param Helper $helper
     */
    public function __construct(Config $config, Helper $helper)
    {
        $this->config = $config;
        $this->helper = $helper;
    }

    /**
     * Select all attributes who should be exported
     *
     * @param EavIterator $iterator
     * @return $this
     */
    public function initializeAttributes(EavIterator $iterator): self
    {
        // Add default attributes
        $iterator->selectAttribute('name');
        $iterator->selectAttribute('sku');
        $iterator->selectAttribute('url_key');
        $iterator->selectAttribute('status');
        $iterator->selectAttribute('visibility');
        $iterator->selectAttribute('type_id');

        // Add configured attributes
        $type = $this->config->getEntityType(Product::ENTITY);

        /** @var Attribute $attribute */
        foreach ($type->getAttributeCollection() as $attribute) {
            if (!$this->helper->shouldExportAttribute($attribute)) {
                continue;
            }

            $iterator->selectAttribute($attribute->getAttributeCode());
        }
        return $this;
    }
}
