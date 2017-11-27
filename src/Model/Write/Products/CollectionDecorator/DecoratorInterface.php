<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Write\Products\Collection;

interface DecoratorInterface
{
    /**
     * Decorate items with extra data or remove items completely
     *
     * @param Collection $collection
     */
    public function decorate(Collection $collection);
}