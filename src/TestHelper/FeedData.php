<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\TestHelper;

use SimpleXMLElement;

class FeedData
{
    /**
     * @param string $id
     * @param array $attributes
     * @param string $sku
     * @return bool
     */
    protected function elementMatchSku(string $id, array $attributes, string $sku): bool
    {
        if (!isset($attributes['sku'])) {
            return $sku === $id;
        }

        if (!\is_array($attributes['sku'])) {
            return $attributes['sku'] === $sku;
        }

        return \in_array($sku, $attributes['sku'], true);
    }

    /**
     * @param SimpleXMLElement $feed
     * @param string $sku
     * @return array|null
     */
    public function getProductData(SimpleXMLElement $feed, string $sku)
    {
        foreach ($feed->xpath('//item') as $element) {
            $id = (string) $element->id;
            $attributes = $this->getItemAttributes($element);

            if (!$this->elementMatchSku($id, $attributes, $sku)) {
                continue;
            }

            return [
                'xml' => $element,
                'id' => $id,
                'name' => (string) $element->name,
                'price' => (float) $element->price,
                'attributes' => $attributes,
                'categories' => $this->getItemCategories($element),
            ];
        }

        return null;
    }

    /**
     * @param SimpleXMLElement $element
     * @return array
     */
    private function getItemCategories(SimpleXMLElement $element): array
    {
        $result = [];
        foreach ($element->categories->children() as $categoryElement) {
            $result[] = (string) $categoryElement;
        }
        return $result;
    }

    /**
     * @param SimpleXMLElement $element
     * @return array
     */
    private function getItemAttributes(SimpleXMLElement $element): array
    {
        $result = [];
        foreach ($element->attributes->children() as $attributeElement) {
            $name = (string) $attributeElement->name;
            $value = (string) $attributeElement->value;

            if (isset($result[$name])) {
                // Ensure data is array
                if (!\is_array($result[$name])) {
                    $result[$name] = [$result[$name]];
                }

                $result[$name][] = $value;
            } else {
                $result[$name] = $value;
            }
        }
        return $result;
    }
}