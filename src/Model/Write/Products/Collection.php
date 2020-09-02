<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use ArrayIterator;
use Countable;
use Emico\TweakwiseExport\Exception\InvalidArgumentException;
use IteratorAggregate;
use Magento\Store\Model\Store;

class Collection implements IteratorAggregate, Countable
{
    /**
     * @var ExportEntity[]
     */
    protected $entities = [];

    /**
     * @var Store
     */
    protected $store;

    /**
     * @var string[]
     */
    protected $skus;

    /**
     * @var int[]
     */
    protected $ids;

    /**
     * Collection constructor.
     * @param Store $store
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
    }

    /**
     * @param ExportEntity $entity
     */
    public function add(ExportEntity $entity): void
    {
        $this->entities[$entity->getId()] = $entity;
    }

    /**
     * @return ExportEntity[]|ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->entities);
    }

    /**
     * {@inheritdoc}
     */
    public function count(): int
    {
        return count($this->entities);
    }

    /**
     * @return Store
     */
    public function getStore(): Store
    {
        return $this->store;
    }

    /**
     * @return int[]
     */
    public function getIds(): array
    {
        return array_keys($this->entities);
    }

    /**
     * @param int $id
     * @return ExportEntity
     */
    public function get(int $id): ExportEntity
    {
        if (!$this->has($id)) {
            throw new InvalidArgumentException(sprintf('Could not find export entity with id %s', $id));
        }

        return $this->entities[$id];
    }

    /**
     * @param int $id
     * @return bool
     */
    public function has(int $id): bool
    {
        return isset($this->entities[$id]);
    }

    /**
     * @return ExportEntity[]
     */
    public function getExported(): array
    {
        $result = [];
        foreach ($this as $entity) {
            if (!$entity->shouldExport()) {
                continue;
            }

            $result[$entity->getId()] = $entity;
        }
        return $result;
    }

    /**
     * @return ExportEntity[]
     */
    public function getAllEntities(): array
    {
        return $this->entities;
    }

    /**
     * Ensure
     */
    protected function ensureIdsAndSkus(): void
    {
        $ids = [];
        $skus = [];
        foreach ($this as $entity) {
            $ids[] = $entity->getId();
            $skus[] = $entity->getAttribute('sku', false);

            if ($entity instanceof CompositeExportEntityInterface) {
                foreach ($entity->getAllChildren() as $child) {
                    $ids[] = $child->getId();
                    $skus[] = $child->getAttribute('sku', false);
                }
            }
        }
        // Make unique
        $this->ids = array_flip($ids);
        $this->skus = array_flip($skus);
    }

    /**
     * Fetches all entity ID's including children
     *
     * @return int[]
     */
    public function getAllIds(): array
    {
        if ($this->ids === null) {
            $this->ensureIdsAndSkus();
        }

        return array_keys($this->ids);
    }

    /**
     * @return array
     */
    public function getAllSkus(): array
    {
        if ($this->skus === null) {
            $this->ensureIdsAndSkus();
        }

        return array_keys($this->skus);
    }

    /**
     * Allow for removal of export entities
     *
     * @param int $id
     */
    public function remove(int $id): void
    {
        $entity = $this->entities[$id] ?? null;
        unset($this->entities[$id], $this->ids[$id]);

        if (!$entity) {
            return;
        }
        try {
            /** @var string $sku */
            $sku = $entity->getAttribute('sku', false);
            unset($this->skus[$sku]);
        } catch (InvalidArgumentException $e) {
            // Wont happen in practice
        }
    }

    /**
     * Allow for removal of export entities
     *
     * @param ExportEntity $exportEntity
     */
    public function removeExportEntity(ExportEntity $exportEntity): void
    {
        $this->remove($exportEntity->getId());
    }
}
