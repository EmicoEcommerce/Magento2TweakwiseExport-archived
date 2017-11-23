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
     * @param SimpleXMLElement $feed
     * @param string $sku
     * @return array|null
     */
    public function getProductData(SimpleXMLElement $feed, string $sku)
    {
        foreach ($feed->xpath('//item') as $element) {
            $data = [
                'xml' => $element,
                'id' => (string) $element->id,
                'name' => (string) $element->name,
                'price' => (float) $element->price,
                'attributes' => $this->getItemAttributes($element),
                'categories' => $this->getItemCategories($element),
            ];

            $key = $data['attributes']['sku'] ?? $data['id'];
            if ($key === $sku) {
                return $data;
            }
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