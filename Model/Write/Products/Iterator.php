<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2TweakwiseExport\Model\Write\Products;

use Tweakwise\Magento2TweakwiseExport\Model\Helper;
use Tweakwise\Magento2TweakwiseExport\Model\Write\EavIterator;
use Tweakwise\Magento2TweakwiseExport\Model\Write\Products\CollectionDecorator\DecoratorInterface;
use Generator;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\Event\Manager;
use Magento\Framework\Model\ResourceModel\Db\Context as DbContext;
use Tweakwise\Magento2TweakwiseExport\Model\Config as TweakwiseConfig;

/**
 * Class Iterator
 * @package Tweakwise\Magento2TweakwiseExport\Model\Write\Products
 */
class Iterator extends EavIterator
{
    /**
     * @var ExportEntityFactory
     */
    protected $entityFactory;

    /**
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * @var DecoratorInterface[]
     */
    protected $collectionDecorators;

    /**
     * Iterator constructor.
     *
     * @param int $batchSize
     * @param Helper $helper
     * @param EavConfig $eavConfig
     * @param DbContext $dbContext
     * @param ExportEntityFactory $entityFactory
     * @param CollectionFactory $collectionFactory
     * @param IteratorInitializer $iteratorInitializer
     * @param DecoratorInterface[] $collectionDecorators
     * @param TweakwiseConfig $config
     */
    public function __construct(
        Helper $helper,
        EavConfig $eavConfig,
        DbContext $dbContext,
        Manager $eventManager,
        ExportEntityFactory $entityFactory,
        CollectionFactory $collectionFactory,
        IteratorInitializer $iteratorInitializer,
        array $collectionDecorators,
        TweakwiseConfig $config
    ) {
        parent::__construct(
            $helper,
            $eavConfig,
            $dbContext,
            $eventManager,
            Product::ENTITY,
            [],
            $config->getBatchSizeProducts()
        );

        $this->entityFactory = $entityFactory;
        $this->collectionFactory = $collectionFactory;
        $this->collectionDecorators = $collectionDecorators;

        $iteratorInitializer->initializeAttributes($this);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator() : \Traversable
    {
        $batch = $this->collectionFactory->create(['store' => $this->store]);
        foreach (parent::getIterator() as $entityData) {
            $entity = $this->entityFactory->create(
                [
                    'store' => $this->store,
                    'data' => $entityData
                ]
            );
            if (!$entity->shouldProcess()) {
                continue;
            }

            $batch->add($entity);

            if ($batch->count() === $this->batchSize) {
                // After PHP7+ we can use yield from
                foreach ($this->processBatch($batch) as $processedEntity) {
                    yield $processedEntity;
                }
                $batch = $this->collectionFactory->create(['store' => $this->store]);
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

        foreach ($collection->getExported() as $entity) {
            yield [
                'entity_id' => $entity->getId(),
                'name' => $entity->getName(),
                'price' => $entity->getPrice(),
                'stock' => (int) round($entity->getStockQty()),
                'categories' => $entity->getCategories(),
                'attributes' => $entity->getAttributes(),
            ];
        }
    }
}
