<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2TweakwiseExport\App\Response;

use Tweakwise\Magento2TweakwiseExport\Model\Export;
use Tweakwise\Magento2TweakwiseExport\Model\Logger;
use Exception;

/**
 * Class FeedContent
 *
 * To string wrapper so output is not stored in memory but written to output on get content
 *
 * @package Tweakwise\Magento2TweakwiseExport\App\Response
 */
class FeedContent
{
    /**
     * @var Export
     */
    protected $export;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * SomeFeedResponse constructor.
     *
     * @param Export $export
     * @param Logger $log
     */
    public function __construct(Export $export, Logger $log)
    {
        $this->export = $export;
        $this->log = $log;
    }

    /**
     * Also renders feed to output stream
     *
     * @return string
     */
    public function __toString()
    {
        $resource = fopen('php://output', 'wb');
        try {
            try {
                $this->export->getFeed($resource);
            } catch (Exception $e) {
                $this->log->error(sprintf('Failed to get feed due to %s', $e->getMessage()));
            }
        } finally {
            fclose($resource);
        }

        return '';
    }
}
