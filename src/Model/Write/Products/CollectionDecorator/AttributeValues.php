<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Write\Products\Collection;

class AttributeValues extends AbstractDecorator
{
    /**
     * {@inheritDoc}
     */
    public function decorate(Collection $collection)
    {

    }

    /**
     * @param string $attributeCode
     * @param mixed $value
     * @return mixed
     */
    protected function filterEntityAttributeValue($attributeCode, $value)
    {
        if (!isset($this->attributesByCode[$attributeCode])) {
            return $value;
        }

        $attribute = $this->attributesByCode[$attributeCode];

        // Text values are ok like this
        if (\in_array($attribute->getBackendModel(), ['static', 'varchar', 'text', 'datetime'], true)) {
            return $value;
        }

        // Decimal values can be cast
        if ($attribute->getBackendModel() === 'decimal') {
            // Cleanup empty values
            $value = trim($value);
            if (empty($value)) {
                return null;
            }

            return (float) $value;
        }

        // Convert int backend
        if ($attribute->getBackendModel() === 'int') {
            // If select or multi select skip
            if ($attribute->getFrontendInput() === 'select' || $attribute->getFrontendInput() === 'multiselect') {
                return $value;
            }

            // Cleanup empty values
            $value = trim($value);
            if (empty($value)) {
                return null;
            }

            return (int) $value;

        }

        return $value;
    }
}