<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\App\Response;

use Emico\TweakwiseExport\Model\Export;

/**
 * Class FeedContent
 *
 * To string wrapper so output is not stored in memory but written to output on get content
 *
 * @package Emico\TweakwiseExport\App\Response
 */
class FeedContent {
    /**
     * @var Export
     */
    protected $export;

    /**
     * SomeFeedResponse constructor.
     *
     * @param Export $export
     */
    public function __construct(Export $export)
    {
        $this->export = $export;
    }

    /**
     * Also renders feed to output stream
     *
     * @return string
     */
    public function __toString()
    {
        $resource = fopen('php://output', 'w');
        try {
            $this->export->getFeed($resource);
        } finally {
            fclose($resource);
        }

        return '';
    }
}