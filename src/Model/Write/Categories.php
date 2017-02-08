<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Model\Write;

class Categories implements WriterInterface
{
    /**
     * @var EavIterator
     */
    protected $iterator;

    /**
     * @var int
     */
    protected $exportedCategories = [];

    /**
     * Categories constructor.
     *
     * @param EavIterator $iterator
     */
    public function __construct(EavIterator $iterator)
    {
        $this->iterator = $iterator;
    }

    /**
     * {@inheritdoc}
     */
    public function write(Writer $writer, XmlWriter $xml)
    {
        $storeId = 1;
        $this->exportedCategories = [];
        $xml->startAttribute('categories');

        foreach ($this->iterator as $data) {
            // Always write root category
            if ($data['entity_id'] == 1) {
                $this->writeCategory($xml, $storeId, $data);
            } elseif ($data['is_active']) {
                $this->writeCategory($xml, $storeId, $data);
            }
            $writer->flush();
        }

        $xml->endAttribute();
        $writer->flush();
        return $this;
    }

    /**
     * @param int $storeId
     * @param int $entityId
     * @return string
     */
    protected function getTweakwiseId($storeId, $entityId)
    {
        return $storeId . '-' . $entityId;
    }

    /**
     * @param XmlWriter $xml
     * @param int $storeId
     * @param array $data
     * @return $this
     */
    protected function writeCategory(XmlWriter $xml, $storeId, array $data)
    {
        $tweakwiseId = $this->getTweakwiseId($storeId, $data['entity_id']);

        $this->exportedCategories[$tweakwiseId] = true;

        $xml->startElement('category');
        $xml->writeElement('categoryid', $data['entity_id']);
        $xml->writeElement('rank', $data['position']);
        $xml->writeElement('name', $data['name']);

        if (isset($data['parent_id']) && $data['parent_id']) {
            $xml->startElement('parents');
            $xml->writeElement('categoryid', $this->getTweakwiseId($storeId, $data['parent_id']));
            $xml->endElement(); // </parents>
        }

        $xml->endElement(); // </category>

        return $this;
    }
}