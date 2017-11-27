<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\TestHelper\Data;

use Zend\Hydrator\ClassMethods;
use Zend\Hydrator\HydratorInterface;

class EntityHydrator implements HydratorInterface
{
    /**
     * @var ClassMethods
     */
    private $classMethods;

    /**
     * EntityHydrator constructor.
     */
    public function __construct()
    {
        $this->classMethods = new ClassMethods();
    }

    /**
     * {@inheritdoc}
     */
    public function extract($object)
    {
        if (method_exists($object, 'getData')) {
            return $object->getData();
        }

        return $this->classMethods->extract($object);
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate(array $data, $object)
    {
        if (method_exists($object, 'addData')) {
            $object->addData($data);
            return $object;
        }

        return $this->classMethods->hydrate($data, $object);
    }
}