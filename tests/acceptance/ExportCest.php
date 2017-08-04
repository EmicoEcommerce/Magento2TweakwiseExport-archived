<?php

namespace Emico\TweakwiseExport;

use AcceptanceTester;
use SimpleXMLElement;
use Symfony\Component\Process\Process;

class ExportCest
{
    /**
     * @var SimpleXMLElement
     */
    protected $feed;

    /**
     * @var array[]
     */
    protected $exportItems = null;

    /**
     * @return SimpleXMLElement
     */
    protected function getFeed()
    {
        if (!$this->feed) {
            $this->feed = simplexml_load_file('var/feeds/tweakwise.xml');
        }

        return $this->feed;
    }

    /**
     * @param string $sku
     * @return null|array
     */
    protected function getProductItem($sku)
    {
        if ($this->exportItems === null) {
            $this->exportItems = [];
            foreach ($this->getFeed()->xpath('//item') as $element) {
                $data = [
                    'xml' => $element,
                    'id' => (string) $element->id,
                    'attributes' => [],
                    'categories' => [],
                ];

                foreach ($element->attributes->children() as $attributeElement) {
                    $name = (string) $attributeElement->name;
                    $value = (string) $attributeElement->value;
                    $data['attributes'][$name] = $value;
                }

                foreach ($element->categories->children() as $categoryElement) {
                    $data['categories'][] = (string) $categoryElement;
                }

                $key = isset($data['attributes']['sku']) ? $data['attributes']['sku'] : $data['id'];
                $this->exportItems[$key] = $data;
            }
        }

        return isset($this->exportItems[$sku]) ? $this->exportItems[$sku] : null;
    }

    /**
     * @param string $sku
     * @param string $attribute
     * @return null|string
     */
    protected function getProductAttribute($sku, $attribute)
    {
        $item = $this->getProductItem($sku);
        if (!$item) {
            return null;
        }

        if (!isset($item['attributes'][$attribute])) {
            return null;
        }

        return $item['attributes'][$attribute];
    }

    /**
     * @param AcceptanceTester $i
     */
    public function runExport(AcceptanceTester $i)
    {
        $process = new Process('./bin/magento tweakwise:export');
        $process->setTimeout(120);
        $process->run();

        $i->assertEquals(0, $process->getExitCode(), 'Export did not run successfully: ' . $process->getErrorOutput());
    }

    /**
     * @param AcceptanceTester $i
     */
    public function testEmptyAttribute(AcceptanceTester $i)
    {
        $value = $this->getProductAttribute('etw-empty-attr', 'special_price');
        $i->assertNull($value);
        $value = $this->getProductAttribute('etw-valid', 'special_price');
        $i->assertNotNull($value);
    }

    /**
     * @param AcceptanceTester $i
     */
    public function testNotExportingDisabledProduct(AcceptanceTester $i)
    {
        $value = $this->getProductItem('etw-disabled');
        $i->assertNull($value);
        $value = $this->getProductItem('etw-valid');
        $i->assertNotNull($value);
    }

    /**
     * @param AcceptanceTester $i
     */
    public function testExportMultipleCategories(AcceptanceTester $i)
    {
        $value = $this->getProductItem('etw-valid');
        $i->assertNotNull($value);
        $i->assertCount(3, $value['categories']);
    }
}
