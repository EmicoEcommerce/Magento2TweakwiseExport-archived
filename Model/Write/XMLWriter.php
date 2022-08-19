<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2TweakwiseExport\Model\Write;

use XMLWriter as BaseXMLWriter;

class XMLWriter extends BaseXMLWriter
{
    /**
     * @var int[]
     */
    protected $categories = [];

    /**
     * Write value in a single element. $value must be a scalar value
     *
     * @param string $elementName
     * @param mixed $value
     * @return $this
     */

    #[\ReturnTypeWillChange]
    public function writeElement($elementName, $value = null)
    {
        parent::startElement($elementName);
        if (!is_numeric($value) && !empty($value)) {
            $this->startCdata();
        }

        if ($value) {
            $value = $this->xmlPrepare($value);
        }
        $this->text($value);

        if (!is_numeric($value) && !empty($value)) {
            $this->endCdata();
        }
        parent::endElement();

        return $this;
    }

    /**
     * @param string $value
     * @return string
     */
    protected function xmlPrepare($value)
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '', $value);
    }

    /**
     * @param int $categoryId
     */
    public function addCategoryExport($categoryId): void
    {
        $categoryId = (int) $categoryId;
        $this->categories[$categoryId] = true;
    }

    /**
     * @param int $categoryId
     * @return bool
     */
    public function hasCategoryExport($categoryId): bool
    {
        $categoryId = (int) $categoryId;
        return isset($this->categories[$categoryId]);
    }
}
