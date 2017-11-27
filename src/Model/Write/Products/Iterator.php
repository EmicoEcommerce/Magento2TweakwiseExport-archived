<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use Emico\TweakwiseExport\Model\Helper;
use Emico\TweakwiseExport\Model\Write\EavIterator;
use Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator\DecoratorInterface;
use Generator;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;

class Iterator extends EavIterator
{
    /**
     * Collection size to fetch extra product data.
     */
    const BATCH_SIZE = 1000;

    /**
     * @var ExportEntityFactory
     */
    private $entityFactory;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var DecoratorInterface[]
     */
    private $collectionDecorators;

    /**
     * Iterator constructor.
     *
     * @param Helper $helper
     * @param EavConfig $eavConfig
     * @param DbContext $dbContext
     * @param ExportEntityFactory $entityFactory
     * @param CollectionFactory $collectionFactory
     * @param DecoratorInterface[] $collectionDecorators
     */
    public function __construct(
        Helper $helper,
        EavConfig $eavConfig,
        DbContext $dbContext,
        ExportEntityFactory $entityFactory,
        CollectionFactory $collectionFactory,
        array $collectionDecorators
    ) {
        parent::__construct($helper, $eavConfig, $dbContext, Product::ENTITY, []);

        $this->entityFactory = $entityFactory;
        $this->collectionFactory = $collectionFactory;
        $this->collectionDecorators = $collectionDecorators;

        $this->initializeAttributes($this);
    }

    /**
     * Select all attributes who should be exported
     *
     * @param EavIterator $iterator
     * @return $this
     * @throws LocalizedException
     */
    protected function initializeAttributes(EavIterator $iterator): self
    {
        // Add default attributes
        $iterator->selectAttribute('name');
        $iterator->selectAttribute('sku');
        $iterator->selectAttribute('url_key');
        $iterator->selectAttribute('status');
        $iterator->selectAttribute('visibility');
        $iterator->selectAttribute('type_id');

        // Add configured attributes
        $type = $this->eavConfig->getEntityType($this->entityCode);

        /** @var Attribute $attribute */
        foreach ($type->getAttributeCollection() as $attribute) {
            if (!$this->helper->shouldExportAttribute($attribute)) {
                continue;
            }

            $iterator->selectAttribute($attribute->getAttributeCode());
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        $batch = $this->collectionFactory->create(['storeId' => $this->storeId]);
        foreach (parent::getIterator() as $entityData) {
            $entity = $this->entityFactory->create(['data' => $entityData]);
            if (!$entity->shouldExport()) {
                continue;
            }

            $batch->add($entity);

            if ($batch->count() === self::BATCH_SIZE) {
                // After PHP7+ we can use yield from
                foreach ($this->processBatch($batch) as $processedEntity) {
                    yield $processedEntity;
                }
                $batch = $this->collectionFactory->create();
            }
        }

        // After PHP7+ we can use yield from
        foreach ($this->processBatch($batch) as $processedEntity) {
            yield $processedEntity;
        }
    }

    /**
     * @param Collection $collection
     * @return Generator
     */
    protected function processBatch(Collection $collection)
    {
        if ($collection->count()) {
            foreach ($this->collectionDecorators as $decorator) {
                $decorator->decorate($collection);
            }
        }

        foreach ($collection as $entity) {
            yield [
                'entity_id' => $entity->getId(),
                'name' => $entity->getName(),
                'price' => $entity->getPrice(),
                'stock' => $entity->getStockQty(),
                'categories' => $entity->getCategories(),
                'attributes' => $entity->getAttributes(),
            ];
        }
    }
}
