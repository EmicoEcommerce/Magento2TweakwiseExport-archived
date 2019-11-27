<?php
/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2019.
 */

namespace Emico\TweakwiseExport\Model;

use Magento\Framework\App\Cache\Manager;

/**
 * Class CacheHandler flush caches after tweakwise publish task
 *
 * @package Emico\TweakwiseExport\Model
 */
class CacheHandler
{
    /**
     * Cache types to be flushed
     */
    const CACHE_TYPES = [
        'block_html',
        'collections',
        'full_page'
    ];

    /**
     * @var Manager Magento native cache handler
     */
    protected $manager;

    /**
     * CacheHandler constructor.
     *
     * @param Manager $manager Cache manager
     */
    public function __construct(Manager $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Cache types to flush, mostly here so that it is available for interception
     *
     * @return string[]
     */
    public function getCacheTypes(): array
    {
        return self::CACHE_TYPES;
    }

    /**
     * Flush Caches
     *
     * @return void
     */
    public function clear()
    {
        $this->manager->flush($this->getCacheTypes());
    }
}
