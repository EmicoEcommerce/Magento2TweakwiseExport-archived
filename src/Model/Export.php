<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Model;

use Emico\TweakwiseExport\Exception\FeedException;
use Emico\TweakwiseExport\Exception\LockException;
use Emico\TweakwiseExport\Model\Validate\Validator;
use Emico\TweakwiseExport\Model\Write\Writer;

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
     * Export constructor.
     *
     * @param Config $config
     * @param Validator $validator
     * @param Writer $writer
     */
    public function __construct(Config $config, Validator $validator, Writer $writer)
    {
        $this->config = $config;
        $this->validator = $validator;
        $this->writer = $writer;
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
        $lockFile = $this->config->getDefaultFeedPath() . '.lock';
        $lockHandle = @fopen($lockFile, 'w');
        if (!$lockHandle) {
            throw new LockException(sprintf('Could not lock feed export "%s"', $lockFile));
        }

        if (flock($lockHandle, LOCK_EX)) {
            try {
                $this->writer->write($targetHandle);
            } finally {
                flock($lockHandle, LOCK_UN);
                fclose($lockHandle);
            }
        } else {
            throw new LockException('Unable to obtain lock');
        }

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

        $feedFile = $this->config->getDefaultFeedPath();
        if (file_exists($feedFile)) {
            $sourceHandle = @fopen($feedFile, 'r');
            if (!$sourceHandle) {
                throw new FeedException(sprintf('Could not open feed path "%s" for reading', $feedFile));
            }

            while (!feof($sourceHandle)) {
                fwrite($sourceHandle, fread($sourceHandle, self::FEED_COPY_BUFFER_SIZE));
            }
            fclose($sourceHandle);
        } else {
            $tmpFeedFile = $feedFile . '.tmp';
            $sourceHandle = @fopen($tmpFeedFile, 'w');
            if (!$sourceHandle) {
                throw new FeedException(sprintf('Could not open feed path "%s" for writing', $feedFile));
            }

            try {
                $this->generateFeed($sourceHandle);
            } finally {
                fclose($sourceHandle);
            }

            if ($this->config->isValidate()) {
                $this->validator->validate($tmpFeedFile);
            }

            rename($tmpFeedFile, $feedFile);
            $this->getFeed($sourceHandle);
        }

        return $this;
    }
}
