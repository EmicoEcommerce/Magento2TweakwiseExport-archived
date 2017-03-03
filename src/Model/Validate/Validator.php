<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Model\Validate;

use Emico\TweakwiseExport\Exception\ValidationException;
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
            Profiler::start('tweakwise::export::validate');
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
            Profiler::stop('tweakwise::export::validate');
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
                throw new ValidationException(__('Category parent reference %s not found', $categoryId));
            }
        }

        foreach ($xml->xpath('/tweakwise/items/item/categories/categoryid') as $categoryIdElement) {
            $categoryId = (string) $categoryIdElement;
            if (!isset($categoryIds[$categoryId])) {
                throw new ValidationException(__('Product category reference %s not found', $categoryId));
            }
        }
    }
}
