<?php

namespace Tweakwise\Magento2TweakwiseExport\TestHelper\FeedData;

use SimpleXMLElement;

class CategoryData
{
    /**
     * @var SimpleXMLElement
     */
    protected $element;

    /**
     * CategoryData constructor.
     *
     * @param SimpleXMLElement $element
     */
    public function __construct(SimpleXMLElement $element)
    {
        $this->element = $element;
    }
}
