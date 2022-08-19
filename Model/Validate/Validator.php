<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2TweakwiseExport\Model\Validate;

use Tweakwise\Magento2TweakwiseExport\Exception\ValidationException;
use Magento\Framework\Profiler;
use SimpleXMLElement;

class Validator
{
    /**
     * @param string $file
     * @throws ValidationException
     */
    public function validate($file)
    {
        $internalXmlErrors = libxml_use_internal_errors(true);

        try {
            Profiler::start('validate');
            $xml = @simplexml_load_file($file);
            if (!$xml) {
                $errors = ['Failed loading XML'];
                foreach(libxml_get_errors() as $error) {
                    $errors[] = $error->message;
                }
                throw new ValidationException(join(PHP_EOL, $errors));
            }
            $this->validateCategoryLinks($xml);
        } finally {
            libxml_use_internal_errors($internalXmlErrors);
            Profiler::stop('validate');
        }
    }

    /**
     * Validate category parent en product category references.
     *
     * @param SimpleXMLElement $xml
     * @throws ValidationException
     */
    protected function validateCategoryLinks(SimpleXMLElement $xml)
    {
        $categoryIdElements = $xml->xpath('/tweakwise/categories/category/categoryid');
        $categoryIds = array();
        foreach ($categoryIdElements as $id) {
            $categoryIds[] = (string) $id;
        }
        $categoryIds = array_flip($categoryIds);

        foreach ($xml->xpath('/tweakwise/categories/category/parents/categoryid') as $categoryIdElement) {
            $categoryId = (string) $categoryIdElement;
            if (!isset($categoryIds[$categoryId])) {
                throw new ValidationException(__(sprintf('Category parent reference %s not found', $categoryId)));
            }
        }

        foreach ($xml->xpath('/tweakwise/items/item/categories/categoryid') as $categoryIdElement) {
            $categoryId = (string) $categoryIdElement;
            if (!isset($categoryIds[$categoryId])) {
                throw new ValidationException(__(sprintf('Product category reference %s not found', $categoryId)));
            }
        }
    }
}
