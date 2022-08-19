<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2TweakwiseExport\Model;

use Tweakwise\Magento2TweakwiseExport\Exception\FeedException;
use Tweakwise\Magento2TweakwiseExport\Exception\LockException;
use Tweakwise\Magento2TweakwiseExport\Model\Validate\Validator;
use Tweakwise\Magento2TweakwiseExport\Model\Write\Writer;
use Exception;
use Magento\Framework\Profiler;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Zend\Http\Client as HttpClient;

/**
 * Class Export
 *
 * handles locking feed and deciding between live export, validation etc. Also throws the events for around the generation actions.
 *
 * @package Tweakwise\Magento2TweakwiseExport\Model
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
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Export constructor.
     *
     * @param Config $config
     * @param Validator $validator
     * @param Writer $writer
     * @param Logger $log
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(Config $config, Validator $validator, Writer $writer, Logger $log, StoreManagerInterface $storeManager)
    {
        $this->config = $config;
        $this->validator = $validator;
        $this->writer = $writer;
        $this->log = $log;
        $this->storeManager = $storeManager;
    }

    /**
     * @param callable $action
     * @param StoreInterface $store
     * @throws Exception
     */
    protected function executeLocked(callable $action, StoreInterface $store = null): void
    {
        Profiler::start('tweakwise::export');
        $lockFile = $this->config->getFeedLockFile(null, $store);

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
     * @param StoreInterface $store
     * @throws Exception
     */
    public function generateFeed($targetHandle, $store): void
    {
        $this->executeLocked(function () use ($targetHandle, $store) {
            $this->writer->write($targetHandle);
            $this->touchFeedGenerateDate($store);
        }, $store);
    }

    /**
     * Get latest generated feed and write to resource or create new if real time is enabled.
     *
     * @param resource $targetHandle
     * @throws Exception
     */
    public function getFeed($targetHandle): void
    {
        $store = null;
        if ($this->config->isStoreLevelExportEnabled()){
            $store = $this->storeManager->getStore();
        }
        if ($this->config->isRealTime()) {
            $this->generateFeed($targetHandle, $store);
        }

        $feedFile = $this->config->getDefaultFeedFile($store);
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
            $this->generateToFile($feedFile, $this->config->isValidate(), $store);
            $this->getFeed($targetHandle);
        }
    }

    /**
     * @param string $feedFile
     * @param bool $validate
     * @param null|StoreInterface $store
     * @throws Exception
     */
    public function generateToFile($feedFile, $validate, $store = null): void
    {
        $this->executeLocked(function () use ($feedFile, $validate, $store) {
            $tmpFeedFile = $this->config->getFeedTmpFile($feedFile, $store);
            $sourceHandle = @fopen($tmpFeedFile, 'wb');

            if (!$sourceHandle) {
                $this->log->throwException(new FeedException(sprintf('Could not open feed path "%s" for writing', $feedFile)));
            }

            try {
                // Write
                try {
                    $this->writer->write($sourceHandle, $store);
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

            $this->touchFeedGenerateDate($store);
            $this->triggerTweakwiseImport();
        }, $store);
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
     * @param null|StoreInterface $store
     *
     * Update last modified time from feed file
     */
    protected function touchFeedGenerateDate($store = null): void
    {
        touch($this->config->getDefaultFeedFile($store));
    }
}
