<?php

/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2020.
 */

namespace Emico\TweakwiseExport\Model\Write\Products;

use Magento\Catalog\Model\Product;
use Magento\Framework\ObjectManagerInterface;
use Magento\Catalog\Model\Product\Type;
use Magento\Framework\DataObject;

class ExportEntityFactory
{
    /**
     * Object Manager instance
     *
     * @var ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var Type
     */
    protected $type;

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     * @param Type $type
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Type $type
    ) {
        $this->_objectManager = $objectManager;
        $this->type = $type;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @return ExportEntity
     */
    public function create(array $data = []): ExportEntity
    {
        $typeId = $data['data']['type_id'] ?? null;
        return $this->_objectManager->create($this->getInstanceType($typeId), $data);
    }

    /**
     * Create child
     *
     * @param array $data
     * @return ExportEntityChild
     */
    public function createChild(array $data = []): ExportEntityChild
    {
        return $this->_objectManager->create(ExportEntityChild::class, $data);
    }

    /**
     * @param string $typeId
     * @return string
     */
    protected function getInstanceType(string $typeId): string
    {
        switch ($typeId) {
            case 'configurable':
                return ExportEntityConfigurable::class;
            case 'grouped':
                return ExportEntityGrouped::class;
            case 'bundle':
                return ExportEntityBundle::class;
            default:
                return $this->getDefaultType($typeId);
        }
    }

    /**
     * @param string $typeId
     * @return string
     */
    protected function getDefaultType(string $typeId): string
    {
        /** @var Product $fakeProduct */
        $fakeProduct = new DataObject(['type_id' => $typeId]);
        $typeModel = $this->type->factory($fakeProduct);
        if ($typeModel->isComposite($fakeProduct)) {
            return CompositeExportEntity::class;
        }

        return ExportEntity::class;
    }
}
