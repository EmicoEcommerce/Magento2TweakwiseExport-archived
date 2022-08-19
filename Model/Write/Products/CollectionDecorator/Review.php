<?php

namespace Tweakwise\Magento2TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Tweakwise\Magento2TweakwiseExport\Exception\InvalidArgumentException;
use Tweakwise\Magento2TweakwiseExport\Model\Review\ProductReviewSummary;
use Tweakwise\Magento2TweakwiseExport\Model\Review\ReviewProviderInterface;
use Tweakwise\Magento2TweakwiseExport\Model\Write\Products\Collection;

/**
 * Class Review
 * Add product reviews to feed
 * @package Tweakwise\Magento2TweakwiseExport\Model\Write\Products\CollectionDecorator
 */
class Review implements DecoratorInterface
{
    /**
     * @var ReviewProviderInterface
     */
    protected $reviewProvider;

    /**
     * Review constructor.
     * @param ReviewProviderInterface $reviewProvider
     */
    public function __construct(ReviewProviderInterface $reviewProvider)
    {
        $this->reviewProvider = $reviewProvider;
    }

    /**
     * Decorate items with extra data or remove items completely
     *
     * @param Collection $collection
     */
    public function decorate(Collection $collection): void
    {
        $reviews = $this->reviewProvider->getProductReviews($collection);
        foreach ($reviews as $review) {
            $productId = $review->getProductId();
            try {
                $exportEntity = $collection->get($productId);
                $exportEntity->addAttribute('review_rating', $review->getAverageRating());
                $exportEntity->addAttribute('review_count', $review->getReviewCount());
            } catch (InvalidArgumentException $e) {
                continue;
            }
        }
    }
}
