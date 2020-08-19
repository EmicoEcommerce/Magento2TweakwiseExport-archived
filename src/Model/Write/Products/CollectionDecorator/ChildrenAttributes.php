<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Emico\TweakwiseExport\Model\Write\Products\CompositeExportEntityInterface;

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
     * {@inheritdoc}
     */
    public function decorate(Collection $collection)
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
