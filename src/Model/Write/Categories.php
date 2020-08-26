<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Model\Write;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Helper;
use Emico\TweakwiseExport\Model\Logger;
use Emico\TweakwiseExport\Model\Write\Categories\Iterator;
use Magento\Framework\Profiler;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManager;

class Categories implements WriterInterface
{
    /**
     * @var Iterator
     */
    protected $iterator;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Helper
     */
    protected $helper;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * Categories constructor.
     *
     * @param Iterator $iterator
     * @param StoreManager $storeManager
     * @param Config $config
     * @param Helper $helper
     * @param Logger $log
     */
    public function __construct(
        Iterator $iterator,
        StoreManager $storeManager,
        Config $config,
        Helper $helper,
        Logger $log
    ) {
        $this->iterator = $iterator;
        $this->storeManager = $storeManager;
        $this->config = $config;
        $this->helper = $helper;
        $this->log = $log;
    }

    /**
     * @param Writer $writer
     * @param XmlWriter $xml
     */
    public function write(Writer $writer, XmlWriter $xml): void
    {
        $xml->startElement('categories');
        $writer->flush();

        $this->writeCategory($xml, 0, ['entity_id' => 1, 'name' => 'Root', 'position' => 0]);
        /** @var Store $store */
        foreach ($this->storeManager->getStores() as $store) {
            if ($this->config->isEnabled($store)) {
                $profileKey = 'categories::' . $store->getCode();
                try {
                    Profiler::start($profileKey);
                    $this->exportStore($writer, $xml, $store);
                } finally {
                    Profiler::stop($profileKey);
                }

                $this->log->debug(sprintf('Export categories for store %s', $store->getName()));
            } else {
                $this->log->debug(sprintf('Skip categories for store %s (disabled)', $store->getName()));
            }
        }

        $xml->endElement(); // categories
        $writer->flush();
    }

    /**
     * @param Writer $writer
     * @param XmlWriter $xml
     * @param Store $store
     */
    protected function exportStore(Writer $writer, XmlWriter $xml, Store $store): void
    {
        // Set root category as exported
        $exportedCategories = [1 => true];
        $storeId = $store->getId();
        $storeRootCategoryId = (int) $store->getRootCategoryId();
        $this->iterator->setStoreId($storeId);
        // Purge iterator entity ids for the new store
        $this->iterator->setEntityIds([]);

        foreach ($this->iterator as $index => $data) {
            // Skip magento root since we injected our fake root
            if ($data['entity_id'] === 1) {
                continue;
            }

            $parentId = (int) $data['parent_id'];
            // Store root category extend name so it is clear in tweakwise
            // Always export store root category whether it is enabled or not
            if ($parentId === 1) {
                // Skip category if not root of current store
                if ($data['entity_id'] !== $storeRootCategoryId) {
                    continue;
                }

                if (!isset($data['name'])) {
                    $data['name'] = 'Root Category';
                }

                $data['name'] = $store->getName() . ' - ' . $data['name'];
            } elseif (!isset($data['is_active']) || !$data['is_active']) {
                continue;
            }

            if (!isset($exportedCategories[$parentId])) {
                continue;
            }

            // Set category as exported
            $exportedCategories[$data['entity_id']] = true;
            $this->writeCategory($xml, $storeId, $data);
            // Flush every so often
            if ($index % 100 === 0) {
                $writer->flush();
            }
        }
        // Flush any remaining categories
        $writer->flush();
    }

    /**
     * @param XmlWriter $xml
     * @param int $storeId
     * @param array $data
     */
    protected function writeCategory(XmlWriter $xml, int $storeId, array $data): void
    {
        $tweakwiseId = $this->helper->getTweakwiseId($storeId, $data['entity_id']);
        $xml->addCategoryExport($tweakwiseId);

        $xml->startElement('category');
        $xml->writeElement('categoryid', $tweakwiseId);
        $xml->writeElement('rank', $data['position']);
        $xml->writeElement('name', $data['name']);

        if (isset($data['parent_id']) && $data['parent_id']) {
            $xml->startElement('parents');

            $parentId = (int) $data['parent_id'];
            if ($parentId !== 1) {
                $parentId = $this->helper->getTweakwiseId($storeId, $parentId);
            }
            $xml->writeElement('categoryid', $parentId);
            $xml->endElement(); // </parents>

            $this->log->debug(sprintf('Export category [%s] %s (parent: %s)', $tweakwiseId, $data['name'], $parentId));
        } else {
            $this->log->debug(sprintf('Export category [%s] %s (root)', $tweakwiseId, $data['name']));
        }

        $xml->endElement(); // </category>
    }
}
