<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\TestHelper\FeedData;

use Emico\TweakwiseExport\Test\TestCase;
use SimpleXMLElement;

class ProductData
{
    /**
     * @var SimpleXMLElement
     */
    private $element;

    /**
     * @var TestCase
     */
    private $test;

    /**
     * @var string
     */
    private $name;

    /**
     * @var float
     */
    private $price;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var array
     */
    private $categories;

    /**
     * CategoryData constructor.
     *
     * @param TestCase $test
     * @param SimpleXMLElement $element
     */
    public function __construct(TestCase $test, SimpleXMLElement $element)
    {
        $this->element = $element;
        $this->test = $test;
    }

    /**
     * @param string $sku
     */
    public function assertSku(string $sku)
    {
        $this->parseAttributes();

        $this->test->assertArrayHasKey('sku', $this->attributes);

        $actualSku = $this->attributes['sku'];
        if (\is_array($actualSku)) {
            $this->test->assertContains($sku, $actualSku);
        } else {
            $this->test->assertEquals($sku, $actualSku);
        }
    }

    /**
     * @param string $name
     */
    public function assertName(string $name)
    {
        $this->parseName();

        $this->test->assertEquals($name, $this->name);
    }

    /**
     * @param float $price
     */
    public function assertPrice(float $price)
    {
        $this->parsePrice();

        $this->test->assertEquals($price, $this->price);
    }

    /**
     * @param array $attributes
     */
    public function assertAttributes(array $attributes)
    {
        $this->parseAttributes();

        foreach ($attributes as $key => $value) {
            $this->test->assertArrayHasKey($key, $this->attributes);
            if (\is_array($value)) {
                asort($value);
            }

            if (\is_array($this->attributes[$key])) {
                asort($this->attributes[$key]);
            }
            $this->test->assertEquals($value, $this->attributes[$key]);
        }
    }

    /**
     * Ensure categories contain
     *
     * @param array $categories
     */
    public function assertCategories(array $categories)
    {
        if ($this->categories === null) {
            $this->parseCategories();
        }

        $this->test->assertArraySubset($categories, $this->categories);
    }

    /**
     * Parse price data from feed
     */
    private function parsePrice()
    {
        if ($this->price !== null) {
            return;
        }
        $this->price = (float) $this->element->price;
    }

    /**
     * Parse name data from feed
     */
    private function parseName()
    {
        if ($this->name !== null) {
            return;
        }

        $this->name = (string) $this->element->name;
    }

    /**
     * Parse categories data from feed
     */
    private function parseCategories()
    {
        if ($this->categories !== null) {
            return;
        }

        $this->categories = [];
        foreach ($this->element->categories->children() as $categoryElement) {
            $this->categories[] = (string) $categoryElement;
        }
    }

    /**
     * Parse attributes data from feed
     */
    private function parseAttributes()
    {
        if ($this->attributes !== null) {
            return;
        }

        $this->attributes = [];
        foreach ($this->element->attributes->children() as $attributeElement) {
            $name = (string) $attributeElement->name;
            $value = (string) $attributeElement->value;

            if (isset($this->attributes[$name])) {
                // Ensure data is array
                if (!\is_array($this->attributes[$name])) {
                    $this->attributes[$name] = [$this->attributes[$name]];
                }

                $this->attributes[$name][] = $value;
            } else {
                $this->attributes[$name] = $value;
            }
        }
    }
}