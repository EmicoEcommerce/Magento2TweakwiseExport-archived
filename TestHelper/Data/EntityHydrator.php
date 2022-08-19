<?php

namespace Tweakwise\Magento2TweakwiseExport\TestHelper\Data;

use ReflectionClass;
use ReflectionException;
use Zend\Filter\Exception\InvalidArgumentException;
use Zend\Filter\Exception\RuntimeException;
use Zend\Filter\FilterChain;
use Zend\Filter\FilterInterface;
use Zend\Filter\Word\UnderscoreToCamelCase;

class EntityHydrator
{
    /**
     * @var string[]
     */
    protected $methodCache;

    /**
     * @var ReflectionClass
     */
    protected $reflectionCache;

    /**
     * @var FilterInterface
     */
    protected $fieldToMethodFilter;

    /**
     * @param array $data
     * @param object $object
     * @return object
     * @throws InvalidArgumentException
     * @throws ReflectionException
     * @throws RuntimeException
     */
    public function hydrate(array $data, $object)
    {
        if (method_exists($object, 'addData')) {
            $object->addData($data);
            return $object;
        }

        $class = \get_class($object);
        foreach ($data as $field => $value) {
            $method = $this->getSetMethod($class, $field);
            if ($method) {
                $object->$method($value);
            }
        }
        return $object;
    }

    /**
     * @return FilterInterface
     * @throws InvalidArgumentException
     */
    protected function getFieldToMethodFilter(): FilterInterface
    {
        if ($this->fieldToMethodFilter === null) {
            $filter = new FilterChain();
            $filter->attach(new UnderscoreToCamelCase());

            $this->fieldToMethodFilter = $filter;
        }
        return $this->fieldToMethodFilter;
    }

    /**
     * @param string $class
     * @return ReflectionClass
     * @throws ReflectionException
     */
    protected function getReflectionClass(string $class): ReflectionClass
    {
        if (!isset($this->reflectionCache[$class])) {
            $this->reflectionCache[$class] = new ReflectionClass($class);
        }
        return $this->reflectionCache[$class];
    }

    /**
     * @param string $class
     * @param string $field
     * @return string|false
     * @throws ReflectionException
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    protected function getSetMethod(string $class, string $field)
    {
        $key = $class . $field;
        if (!isset($this->methodCache[$key])) {
            $method = 'set' . $this->getFieldToMethodFilter()->filter($field);
            $reflection = $this->getReflectionClass($class);

            $this->methodCache[$key] = $reflection->hasMethod($method) ? $method : false;
        }
        return $this->methodCache[$key];
    }
}
