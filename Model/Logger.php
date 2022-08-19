<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2TweakwiseExport\Model;

use Exception;
use Psr\Log\LoggerInterface;

class Logger implements LoggerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * Log constructor.
     *
     * @param LoggerInterface $log
     */
    public function __construct(LoggerInterface $log)
    {
        $this->log = $log;
    }

    /**
     * {@inheritdoc}
     */
    public function emergency($message, array $context = [])
    {
        $this->log->emergency('[TweakWise] ' . $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function alert($message, array $context = [])
    {
        $this->log->alert('[TweakWise] ' . $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function critical($message, array $context = [])
    {
        $this->log->critical('[TweakWise] ' . $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function error($message, array $context = [])
    {
        $this->log->error('[TweakWise] ' . $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function warning($message, array $context = [])
    {
        $this->log->warning('[TweakWise] ' . $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function notice($message, array $context = [])
    {
        $this->log->notice('[TweakWise] ' . $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function info($message, array $context = [])
    {
        $this->log->info('[TweakWise] ' . $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function debug($message, array $context = [])
    {
        $this->log->debug('[TweakWise] ' . $message, $context);
    }

    /**
     * {@inheritdoc}
     */
    public function log($level, $message, array $context = [])
    {
        $this->log->log($level, '[TweakWise] ' . $message, $context);
    }

    /**
     * Log exception message in Tweakwise tag and throw exception
     *
     * @param Exception $exception
     * @throws Exception
     */
    public function throwException(Exception $exception)
    {
        $this->log->error($exception->getMessage());
        throw $exception;
    }
}
