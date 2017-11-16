<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Model;

use Emico\TweakwiseExport\Exception\FeedException;
use Emico\TweakwiseExport\Exception\LockException;
use Emico\TweakwiseExport\Model\Validate\Validator;
use Emico\TweakwiseExport\Model\Write\Writer;
use Exception;
use Magento\Framework\Profiler;
use Zend\Http\Client as HttpClient;

/**
 * Class Export
 *
 * handles locking feed and deciding between live export, validation etc. Also throws the events for around the generation actions.
 *
 * @package Emico\TweakwiseExport\Model
 */
class Export
{
    /**
     * Feed buffer copy size for writing already generated feed to resource
     */
    const FEED_COPY_BUFFER_SIZE = 1024;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * @var Writer
     */
    protected $writer;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * Export constructor.
     *
     * @param Config $config
     * @param Validator $validator
     * @param Writer $writer
     * @param Logger $log
     */
    public function __construct(Config $config, Validator $validator, Writer $writer, Logger $log)
    {
        $this->config = $config;
        $this->validator = $validator;
        $this->writer = $writer;
        $this->log = $log;
    }

    /**
     * @param callable $action
     * @param string|null $lockFile Defaults to default feed path
     * @return $this
     */
    protected function executeLocked(callable $action, $lockFile = null)
    {
        Profiler::start('tweakwise::export');
        if (!$lockFile) {
            $lockFile = $this->config->getDefaultFeedFile() . '.lock';
        }

        try {
            $lockHandle = @fopen($lockFile, 'wb');
            if (!$lockHandle) {
                $this->log->throwException(new LockException(sprintf('Could not lock feed export on lockfile "%s"', $lockFile)));
            }

            if (flock($lockHandle, LOCK_EX)) {
                try {
                    $action();
                } finally {
                    flock($lockHandle, LOCK_UN);
                    fclose($lockHandle);
                }
            } else {
                $this->log->throwException(new LockException(sprintf('Unable to obtain lock on %s', $lockFile)));
            }
        } finally {
            if (file_exists($lockFile)) {
                unlink($lockFile);
            }
            Profiler::stop('tweakwise::export');
        }

        return $this;
    }

    /**
     * Generate and write feed content to handle
     *
     * @param resource $targetHandle
     * @return $this
     * @throws FeedException
     * @throws LockException
     */
    public function generateFeed($targetHandle)
    {
        $this->executeLocked(function () use ($targetHandle) {
            $this->writer->write($targetHandle);
        });
        return $this;
    }

    /**
     * Get latest generated feed and write to resource or create new if real time is enabled.
     *
     * @param resource $targetHandle
     * @return $this
     * @throws FeedException
     */
    public function getFeed($targetHandle)
    {
        if ($this->config->isRealTime()) {
            $this->generateFeed($targetHandle);
            return $this;
        }

        $feedFile = $this->config->getDefaultFeedFile();
        if (file_exists($feedFile)) {
            $sourceHandle = @fopen($feedFile, 'rb');
            if (!$sourceHandle) {
                $this->log->throwException(new FeedException(sprintf('Could not open feed path "%s" for reading', $feedFile)));
            }

            while (!feof($sourceHandle)) {
                fwrite($targetHandle, fread($sourceHandle, self::FEED_COPY_BUFFER_SIZE));
            }
            fclose($sourceHandle);
        } else {
            $this->generateToFile($feedFile, $this->config->isValidate());
            $this->getFeed($targetHandle);
        }

        return $this;
    }

    /**
     * @param string $feedFile
     * @param bool $validate
     * @return $this
     * @throws FeedException
     */
    public function generateToFile($feedFile, $validate)
    {
        $this->executeLocked(function () use ($feedFile, $validate) {
            $tmpFeedFile = $feedFile . '.tmp';
            $sourceHandle = @fopen($tmpFeedFile, 'wb');

            if (!$sourceHandle) {
                $this->log->throwException(new FeedException(sprintf('Could not open feed path "%s" for writing', $feedFile)));
            }

            try {
                try {
                    $this->writer->write($sourceHandle);
                    $this->log->debug('Feed exported to ' . $tmpFeedFile);
                } finally {
                    fclose($sourceHandle);
                }

                if ($validate) {
                    $this->validator->validate($tmpFeedFile);
                    $this->log->debug('Feed validated ' . $tmpFeedFile);
                }

                if (!rename($tmpFeedFile, $feedFile)) {
                    $this->log->debug('Feed rename failed ' . $tmpFeedFile);
                } else {
                    $this->log->debug('Feed renamed ' . $tmpFeedFile);
                }
            } finally {
                if (file_exists($tmpFeedFile)) {
                    unlink($tmpFeedFile);
                }
            }

            $this->triggerTweakwiseImport();
        });
        return $this;
    }

    /**
     * Trigger TW import call if configured
     */
    private function triggerTweakwiseImport()
    {
        $apiImportUrl = $this->config->getApiImportUrl();
        if (empty($apiImportUrl)) {
            $this->log->debug('TW import not triggered, no api import url defined.');
            return;
        }

        try {
            $client = new HttpClient($apiImportUrl);
            $client->send();
            $this->log->debug('TW import triggered');
        } catch (HttpClient\Exception\ExceptionInterface $e) {
            $this->log->error(sprintf('Trigger TW import failed due to %s', $e->getMessage()));
        }
    }
}
