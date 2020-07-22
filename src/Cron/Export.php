<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Cron;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Export as ExportService;
use Emico\TweakwiseExport\Model\Logger;

class Export
{
    /**
     * Code of the cronjob
     */
    const JOB_CODE = 'emico_tweakwise_export';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var ExportService
     */
    protected $export;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * Export constructor.
     *
     * @param Config $config
     * @param ExportService $export
     * @param Logger $log
     */
    public function __construct(Config $config, ExportService $export, Logger $log)
    {
        $this->config = $config;
        $this->export = $export;
        $this->log = $log;
    }

    /**
     * Export feed
     */
    public function execute() {
        if ($this->config->isRealTime()) {
            $this->log->debug('Export set to real time, skipping cron export.');
            return;
        }

        $feedFile = $this->config->getDefaultFeedFile();
        $validate = $this->config->isValidate();
        $this->export->generateToFile($feedFile, $validate);
    }
}
