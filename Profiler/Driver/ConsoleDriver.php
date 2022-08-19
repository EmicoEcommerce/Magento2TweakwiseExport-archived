<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Tweakwise\Magento2TweakwiseExport\Profiler\Driver;

use InvalidArgumentException;
use Magento\Framework\Profiler\Driver\Standard\Stat;
use Magento\Framework\Profiler\DriverInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleDriver implements DriverInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Stat
     */
    protected $stat;

    /**
     * ConsoleDriver constructor.
     *
     * @param OutputInterface $output
     */
    public function __construct(OutputInterface $output)
    {
        $this->output = $output;
        $this->stat = new Stat();
    }

    /**
     * {@inheritdoc}
     */
    public function start($timerId, array $tags = null)
    {
        $this->stat->start($timerId, microtime(true), memory_get_usage(true), memory_get_usage());
        $this->display($timerId);
    }

    /**
     * {@inheritdoc}
     */
    public function stop($timerId)
    {
        $this->stat->stop($timerId, microtime(true), memory_get_usage(true), memory_get_usage());
        $this->display($timerId);
    }

    /**
     * {@inheritdoc}
     */
    public function clear($timerId = null)
    {
        $this->stat->clear($timerId);
        $this->display($timerId);
    }

    /**
     * @param string $timerId
     */
    protected function display($timerId)
    {
        if (!$timerId) {
            return;
        }

        if (strpos($timerId, 'EAV:') !== false) {
            return;
        }

        try {
            $data = $this->stat->get($timerId);
        } catch (InvalidArgumentException $e) {
            return;
        }

        if ($data['start']) {
            $this->output->writeln(sprintf('[CodeProfiler][start] %s', $timerId));
        } else {
            $this->output->writeln(sprintf(
                '[CodeProfiler][stop] %s %s (Memory usage: real - %s, emalloc - %s)',
                $timerId,
                number_format($data[Stat::TIME], 6),
                $data[Stat::REALMEM],
                $data[Stat::EMALLOC]
            ));
        }


    }
}
