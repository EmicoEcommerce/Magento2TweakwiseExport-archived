<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Console\Command;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Export;
use Emico\TweakwiseExport\Profiler\Driver\ConsoleDriver;
use Exception;
use Magento\Framework\App\Area;
use Magento\Framework\App\State;
use Magento\Framework\Profiler;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends Command
{
    /**
     * @var Config
     */
    protected Config $config;

    /**
     * @var Export
     */
    protected Export $export;

    /**
     * @var State
     */
    protected State $state;

    /**
     * ExportCommand constructor.
     *
     * @param Config $config
     * @param Export $export
     * @param State $state
     */
    public function __construct(Config $config, Export $export, State $state)
    {
        $this->config = $config;
        $this->export = $export;
        $this->state = $state;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('tweakwise:export')
            ->addArgument('file', InputArgument::OPTIONAL, 'Export to specific file', $this->config->getDefaultFeedFile())
            ->addOption(
                'validate',
                'c',
                InputOption::VALUE_OPTIONAL, 'Validate feed and rollback if fails [y/n].'
            )
            ->addOption('debug', 'd', InputOption::VALUE_NONE, 'Debugging enables profiler.')
            ->setDescription('Export tweakwise feed');
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->emulateAreaCode(Area::AREA_CRONTAB, function() use ($input, $output) {
            if ($input->getOption('debug')) {
                Profiler::enable();
                Profiler::add(new ConsoleDriver($output));
            }

            $feedFile = (string) $input->getArgument('file');
            $validate = (string) $input->getOption('validate');
            if ($validate !== 'y' && $validate !== 'n' && $validate !== "") {
                $output->writeln('Validate option can only contain y or n');
                return;
            }

            $validate = $validate === "" ? $this->config->isValidate() : $validate === 'y';

            $startTime = microtime(true);
            $this->export->generateToFile($feedFile, $validate);
            $generateTime = round(microtime(true) - $startTime, 2);
            $memoryUsage = round(memory_get_peak_usage(true) / 1024 / 1024, 2);
            $output->writeln(sprintf('Feed written to %s in %ss using %sMb memory', $feedFile, $generateTime, $memoryUsage));
        });

    }
}
