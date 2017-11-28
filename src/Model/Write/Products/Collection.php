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

class Collection implements IteratorAggregate, Countable
{
    /**
     * @var array
     */
    private $entities = [];

    /**
     * @var int
     */
    private $storeId;

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
     * Fetches all entities including children, checked on should export. Array key is entity ID
     *
     * @return ExportEntity[]
     */
    public function getExportedIncludingChildren(): array
    {
        $result = [];
        foreach ($this->getExported() as $entity) {
            $result[$entity->getId()] = $entity;

            foreach ($entity->getExportChildren() as $child) {
                $result[$child->getId()] = $child;
            }
        }
        return $result;
    }
}