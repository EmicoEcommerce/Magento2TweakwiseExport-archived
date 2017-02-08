<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Console\Command;

use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Export;
use Emico\TweakwiseExport\Model\Validate\Validator;
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
    protected $config;

    /**
     * @var Export
     */
    protected $export;

    /**
     * @var Validator
     */
    protected $validator;

    /**
     * ExportCommand constructor.
     *
     * @param Config $config
     * @param Export $export
     * @param Validator $validator
     */
    public function __construct(Config $config, Export $export, Validator $validator)
    {
        $this->config = $config;
        $this->export = $export;
        $this->validator = $validator;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('tweakwise:export')
            ->addArgument('file', InputArgument::OPTIONAL, 'Export to specific file', $this->config->getDefaultFeedPath())
            ->addOption('validate', 'c', InputOption::VALUE_NONE, 'Validate feed and rollback if fails.')
            ->setDescription('Export tweakwise feed');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $feedFile = (string) $input->getArgument('file');
        $validate = (bool) $input->getOption('validate');

        $this->export->generateToFile($feedFile, $validate);
    }
}
