<?php

namespace Emico\TweakwiseExport;

use AcceptanceTester;
use Symfony\Component\Process\Process;

class ExportCest
{
    /**
     * @param AcceptanceTester $i
     */
    public function runExport(AcceptanceTester $i)
    {
        $process = new Process('./bin/magento tweakwise:export');
        $process->setTimeout(120);
        $process->run();

        $i->assertEquals(0, $process, 'Export did not run successfully: ' . $process->getErrorOutput());
    }
}
