<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use ArrayIterator;
use Countable;
use Emico\TweakwiseExport\Exception\InvalidArgumentException;
use Emico\TweakwiseExport\Model\Write\Products\ExportEntity;
use IteratorAggregate;

class Collection implements IteratorAggregate, Countable
{
    /**
     * @var ExportEntity[]
     */
    protected $entities = [];

    /**
     * @var int
     */
    protected $storeId;

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
     * @param int $storeId
     */
    public function __construct(int $storeId)
    {
        $this->storeId = $storeId;
    }

    /**
     * @param ExportEntity $entity
     * @return $this
     */
    public function add(ExportEntity $entity): self
    {
        $this->entities[$entity->getId()] = $entity;
        return $this;
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
    public function count()
    {
        return count($this->entities);
    }

    /**
     * @return int
     */
    public function getStoreId(): int
    {
        return $this->storeId;
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
    public function getAllEntities()
    {
        return $this->entities;
    }

    /**
     * Ensure
     */
    protected function ensureIdsAndSkus()
    {
        $ids = [];
        $skus = [];
        foreach ($this->getExported() as $entity) {
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
     * Fetches all entity ID's including childen
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
    public function remove(int $id)
    {
        unset($this->entities[$id], $this->ids[$id]);
        if (!$entity = $this->entities[$id]) {
            return;
        }

        try {
            /** @var string $sku */
            $sku = $entity->getAttribute('sku', false);
            unset ($this->skus[$sku]);
        } catch (InvalidArgumentException $e) {
            // Wont happen in practice
        }
    }

    /**
     * Allow for removal of export entities
     *
     * @param \Emico\TweakwiseExport\Model\Write\Products\ExportEntity $exportEntity
     */
    public function removeExportEntity(ExportEntity $exportEntity)
    {
        $this->remove($exportEntity->getId());
    }
}
