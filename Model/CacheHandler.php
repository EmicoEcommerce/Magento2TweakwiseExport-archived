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
     * @var Manager Magento native cache handler
     */
    protected Manager $manager;

    /**
     * @var array
     */
    protected array $cacheTypes = [];

    /**
     * CacheHandler constructor.
     *
     * @param Manager $manager Cache manager
     * @param array $cacheTypes Cache types to flush
     */
    public function __construct(Manager $manager, array $cacheTypes = [])
    {
        $this->manager = $manager;
        $this->cacheTypes = $cacheTypes;
    }

    /**
     * Flush Caches
     *
     * @return void
     */
    public function clear(): void
    {
        $this->manager->flush($this->cacheTypes);
    }
}
