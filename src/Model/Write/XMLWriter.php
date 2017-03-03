<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Model\Write;

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
        $result = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F]/u', '', $value);

        return $result;
    }

    /**
     * @param int $categoryId
     * @return $this
     */
    public function addCategoryExport($categoryId)
    {
        $categoryId = (int) $categoryId;
        $this->categories[$categoryId] = true;
        return $this;
    }

    /**
     * @param int $categoryId
     * @return bool
     */
    public function hasCategoryExport($categoryId)
    {
        $categoryId = (int) $categoryId;
        return isset($this->categories[$categoryId]);
    }
}