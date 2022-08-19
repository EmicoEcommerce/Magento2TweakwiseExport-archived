<?php

namespace Tweakwise\Magento2TweakwiseExport\Model\Write\Products;

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
     * @var array
     */
    protected $typeMap;

    /**
     * Factory constructor
     *
     * @param ObjectManagerInterface $objectManager
     * @param Type $type
     * @param array $typeMap
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        Type $type,
        array $typeMap = []
    ) {
        $this->_objectManager = $objectManager;
        $this->type = $type;
        $this->typeMap = $typeMap;
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
        if (!$typeId) {
            $this->_objectManager->create(ExportEntity::class, $data);
        }
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
    protected function getInstanceType(string $typeId = null): string
    {
        return $this->typeMap[$typeId] ?? $this->getDefaultType($typeId);
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
            $this->typeMap[$typeId] = CompositeExportEntity::class;
        }

        $this->typeMap[$typeId] = ExportEntity::class;
        return $this->typeMap[$typeId];
    }
}
