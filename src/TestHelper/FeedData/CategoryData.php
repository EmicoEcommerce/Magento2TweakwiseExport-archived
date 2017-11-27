<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\TestHelper\FeedData;

use SimpleXMLElement;

class CategoryData
{
    /**
     * @var SimpleXMLElement
     */
    private $element;

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