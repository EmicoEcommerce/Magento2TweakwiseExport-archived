<?php

namespace Tweakwise\Magento2TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Tweakwise\Magento2TweakwiseExport\Model\Write\Products\Collection;

interface DecoratorInterface
{
    /**
     * Decorate items with extra data or remove items completely
     *
     * @param Collection $collection
     */
    public function decorate(Collection $collection);
}
