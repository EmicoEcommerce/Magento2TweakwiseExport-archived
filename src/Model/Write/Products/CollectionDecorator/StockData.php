<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Magento\Framework\App\ProductMetadataInterface;

class StockData implements DecoratorInterface
{
    /**
     * @var ProductMetadataInterface
     */
    private $metaData;

    /**
     * @var DecoratorInterface[]
     */
    private $stockDecorators = [];

    /**
     * StockData constructor.
     *
     * @param ProductMetadataInterface $metaData
     * @param DecoratorInterface[] $stockDecorators
     */
    public function __construct(
        ProductMetadataInterface $metaData,
        array $stockDecorators
    ) {
        $this->metaData = $metaData;
        $this->stockDecorators = $stockDecorators;
    }

    /**
     * {@inheritdoc}
     */
    public function decorate(Collection $collection)
    {
        $version = $this->metaData->getVersion();
        if (version_compare($version, '2.3.0', '<')) {
            $this->stockDecorators['V22X']->decorate($collection);
        } else {
            $this->stockDecorators['V23X']->decorate($collection);
        }
    }
}