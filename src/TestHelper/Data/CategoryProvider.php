<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\TestHelper\Data;

class CategoryProvider
{
    /**
     * @return CategoryProvider
     */
    public function clearData(): self
    {
        // Currently nothing to clean up
        return $this;
    }

    /**
     * @return array
     */
    public function getDefaultCategoryIds(): array
    {
        return [2];
    }
}