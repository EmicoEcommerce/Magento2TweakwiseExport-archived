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
    protected const FEED_COPY_BUFFER_SIZE = 1024;

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
     * @throws Exception
     */
    protected function executeLocked(callable $action): void
    {
        Profiler::start('tweakwise::export');
        $lockFile = $this->config->getFeedLockFile();

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
    }

    /**
     * Generate and write feed content to handle
     *
     * @param resource $targetHandle
     * @throws Exception
     */
    public function generateFeed($targetHandle): void
    {
        $this->executeLocked(function () use ($targetHandle) {
            $this->writer->write($targetHandle);
            $this->touchFeedGenerateDate();
        });
    }

    /**
     * Get latest generated feed and write to resource or create new if real time is enabled.
     *
     * @param resource $targetHandle
     * @throws Exception
     */
    public function getFeed($targetHandle): void
    {
        if ($this->config->isRealTime()) {
            $this->generateFeed($targetHandle);
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
    }

    /**
     * @param string $feedFile
     * @param bool $validate
     * @throws Exception
     */
    public function generateToFile($feedFile, $validate): void
    {
        $this->executeLocked(function () use ($feedFile, $validate) {
            $tmpFeedFile = $this->config->getFeedTmpFile($feedFile);
            $sourceHandle = @fopen($tmpFeedFile, 'wb');

            if (!$sourceHandle) {
                $this->log->throwException(new FeedException(sprintf('Could not open feed path "%s" for writing', $feedFile)));
            }

            try {
                // Write
                try {
                    $this->writer->write($sourceHandle);
                    $this->log->debug('Feed exported to ' . $tmpFeedFile);
                } finally {
                    fclose($sourceHandle);
                }

                // Validate
                if ($validate) {
                    $this->validator->validate($tmpFeedFile);
                    $this->log->debug('Feed validated ' . $tmpFeedFile);
                }

                // Archive
                $maxSuffix = $this->config->getMaxArchiveFiles();
                for ($suffix = $maxSuffix; $suffix > 0; $suffix--) {
                    $source = $feedFile . ($suffix > 1 ? '.' . ($suffix - 1) : '');
                    if (!file_exists($source)) {
                        continue;
                    }
                    $target = $feedFile . '.' . $suffix;
                    // Move
                    if (!rename($source, $target)) {
                        $this->log->debug('Archive feed rename failed (' . $source . ' to ' . $target . ')');
                    } else {
                        $this->log->debug('Archive feed renamed (' . $source . ' to ' . $target . ')');
                    }
                }

                // Rename
                if (!rename($tmpFeedFile, $feedFile)) {
                    $this->log->debug('Feed rename failed (' . $tmpFeedFile . ' to ' . $feedFile . ')');
                } else {
                    $this->log->debug('Feed renamed (' . $tmpFeedFile . ' to ' . $feedFile . ')');
                }
            } finally {
                // Remove temporary file
                if (file_exists($tmpFeedFile)) {
                    unlink($tmpFeedFile);
                }
            }

            $this->touchFeedGenerateDate();
            $this->triggerTweakwiseImport();
        });
    }

    /**
     * Trigger TW import call if configured
     */
    protected function triggerTweakwiseImport(): void
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

    /**
     * Update last modified time from feed file
     */
    protected function touchFeedGenerateDate(): void
    {
        touch($this->config->getDefaultFeedFile());
    }
}
