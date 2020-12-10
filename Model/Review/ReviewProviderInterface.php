<?php
/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2019.
 */

namespace Emico\TweakwiseExport\Model\Review;

use Emico\TweakwiseExport\Model\Write\Products\Collection;

/**
 * Interface ReviewProviderInterface
 * @package Emico\TweakwiseExport\Model\Review
 */
interface ReviewProviderInterface
{
    /**
     * @param Collection $collection Tweakwise product collection
     * @return ProductReviewSummary[]
     */
    public function getProductReviews(Collection $collection): array;
}
