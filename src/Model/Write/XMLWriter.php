<?php
/**
 * @author Freek Gruntjes <fgruntjes@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write;

use XMLWriter as BaseXMLWriter;

class XMLWriter extends BaseXMLWriter
{
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

        $this->text($value);

        if (!is_numeric($value) && !empty($value)) {
            $this->endCdata();
        }
        parent::endElement();

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function writeAttribute($name, $value)
    {
        parent::startElement('attribute');
        parent::writeAttribute('datatype', is_numeric($value) ? 'numeric' : 'text');
        $this->writeElement('name', $name);
        $this->writeElement('value', $this->xmlPrepare($value));
        parent::endElement(); // </attribute>

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
}