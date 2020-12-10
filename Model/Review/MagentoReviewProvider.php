<?php
/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2019.
 */

namespace Emico\TweakwiseExport\Model\Review;

use Emico\TweakwiseExport\Model\Write\Products\Collection;
use Magento\Review\Model\ResourceModel\Review\Summary\CollectionFactory as SummaryCollectionFactory;
use Magento\Review\Model\Review\Summary;

/**
 * Class MagentoReviewProvider
 * @package Emico\TweakwiseExport\Model\Review
 */
class MagentoReviewProvider implements ReviewProviderInterface
{
    /**
     * @var SummaryCollectionFactory
     */
    protected $summaryCollectionFactory;

    /**
     * MagentoReviewProvider constructor.
     * @param SummaryCollectionFactory $summaryCollectionFactory
     */
    public function __construct(
        SummaryCollectionFactory $summaryCollectionFactory
    ) {
        $this->summaryCollectionFactory = $summaryCollectionFactory;
    }

    /**
     * @param Collection $collection Tweakwise product collection
     * @return ProductReviewSummary[]
     */
    public function getProductReviews(Collection $collection): array
    {
        $summaryCollection = $this->summaryCollectionFactory->create()
            ->addStoreFilter($collection->getStore()->getId())
            ->addEntityFilter($collection->getAllIds());

        $reviews = [];
        /** @var Summary $rating */
        foreach ($summaryCollection as $summary) {
            $reviews[] = $this->createProductReviewSummary($summary);
        }

        return $reviews;
    }

    /**
     * @param Summary $summary
     * @return ProductReviewSummary
     */
    protected function createProductReviewSummary(Summary $summary): ProductReviewSummary
    {
        return new ProductReviewSummary(
            $summary->getRatingSummary(),
            $summary->getReviewsCount(),
            $summary->getEntityPkValue()
        );
    }
}
