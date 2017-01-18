<?php
namespace Emico\TweakwiseExport\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ExportCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('tweakwise:export:export')
            ->setDescription('Export tweakwise feed');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {

    }
}
