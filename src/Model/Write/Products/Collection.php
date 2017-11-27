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
        if (!isset($this->entities[$id])) {
            throw new InvalidArgumentException(sprintf('Could not find export entity with id %s', $id));
        }

        return $this->entities[$id];
    }

    /**
     * @return int[]
     */
    public function getAllChildIds(): array
    {
        return array_merge(...array_map(function(ExportEntity $entity) { return $entity->getChildIds(); }, $this->entities));
    }
}