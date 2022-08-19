<?php

namespace Tweakwise\Magento2TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Tweakwise\Magento2TweakwiseExport\Model\Config;
use Tweakwise\Magento2TweakwiseExport\Model\Write\Products\Collection;
use Tweakwise\Magento2TweakwiseExport\Model\Write\Products\CompositeExportEntityInterface;

class ChildrenAttributes implements DecoratorInterface
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * ChildrenAttributes constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Decorate items with extra data or remove items completely
     *
     * @param Collection $collection
     */
    public function decorate(Collection $collection): void
    {
        foreach ($collection as $exportEntity) {
            if (!$exportEntity instanceof CompositeExportEntityInterface) {
                continue;
            }

            foreach ($exportEntity->getExportChildren() as $child) {
                foreach ($child->getAttributes() as $attributeData) {
                    if ($this->config->getSkipChildAttribute($attributeData['attribute'])) {
                        continue;
                    }

                    $exportEntity->addAttribute($attributeData['attribute'], $attributeData['value']);
                }
            }
        }
    }
}
