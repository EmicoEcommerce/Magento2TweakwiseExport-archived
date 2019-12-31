<?php
/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2019.
 */

namespace Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator;

use Emico\TweakwiseExport\Exception\InvalidArgumentException;
use Emico\TweakwiseExport\Model\Review\ProductReviewSummary;
use Emico\TweakwiseExport\Model\Review\ReviewProviderInterface;
use Emico\TweakwiseExport\Model\Write\Products\Collection;

/**
 * Class Review
 * Add product reviews to feed
 * @package Emico\TweakwiseExport\Model\Write\Products\CollectionDecorator
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
    public function decorate(Collection $collection)
    {
        $reviews = $this->reviewProvider->getProductReviews($collection);
        /** @var ProductReviewSummary $review */
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
