<?php

namespace Tweakwise\Magento2TweakwiseExport\Model\Review;

use Tweakwise\Magento2TweakwiseExport\Model\Write\Products\Collection;

/**
 * Interface ReviewProviderInterface
 * @package Tweakwise\Magento2TweakwiseExport\Model\Review
 */
interface ReviewProviderInterface
{
    /**
     * @param Collection $collection Tweakwise product collection
     * @return ProductReviewSummary[]
     */
    public function getProductReviews(Collection $collection): array;
}
