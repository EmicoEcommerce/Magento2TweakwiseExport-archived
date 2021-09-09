<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Cron;

use Emico\TweakwiseExport\Exception\FeedException;
use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Export as ExportService;
use Emico\TweakwiseExport\Model\Logger;
use Magento\Store\Model\StoreManagerInterface;

class Export
{
    /**
     * Code of the cronjob
     */
    public const JOB_CODE = 'emico_tweakwise_export';

    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var ExportService
     */
    protected ExportService $export;

    /**
     * @var Logger
     */
    protected Logger $log;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Export constructor.
     *
     * @param Config $config
     * @param ExportService $export
     * @param Logger $log
     */
    public function __construct(
        Config $config,
        ExportService $export,
        Logger $log,
        StoreManagerInterface $storeManager
    ) {
        $this->config = $config;
        $this->export = $export;
        $this->log = $log;
        $this->storeManager = $storeManager;
    }

    /**
     * Export feed
     * @throws \Exception
     */
    public function execute(): void
    {
        if ($this->config->isRealTime()) {
            $this->log->debug('Export set to real time, skipping cron export.');
            return;
        }

        if ($this->storeManager->isSingleStoreMode() && !$this->config->isEnabled()) {
            return;
        }

        $feedFile = $this->config->getDefaultFeedFile();
        $validate = $this->config->isValidate();
        $this->export->generateToFile($feedFile, $validate);
    }
}
