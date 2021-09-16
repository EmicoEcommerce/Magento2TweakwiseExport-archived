<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Model\Write;

use DateTime;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Profiler;
use Magento\Store\Model\StoreManager;
use Magento\Framework\Composer\ComposerInformation;

class Writer
{
    /**
     * @var XMLWriter
     */
    protected ?XMLWriter $xml = null;

    /**
     * Resource where XML is written to after each flush
     *
     * @var Resource
     */
    protected $resource = null;

    /**
     * @var StoreManager
     */
    protected StoreManager $storeManager;

    /**
     * @var AppState
     */
    protected AppState $appState;

    /**
     * @var WriterInterface[]
     */
    protected array $writers = [];

    /**
     * @var DateTime
     */
    protected ?DateTime $now = null;

    /**
     * @var ComposerInformation
     */
    protected ComposerInformation $composerInformation;

    /**
     * Writer constructor.
     *
     * @param StoreManager $storeManager
     * @param AppState $appState
     * @param ComposerInformation $composerInformation
     * @param WriterInterface[] $writers
     */
    public function __construct(
        StoreManager $storeManager,
        AppState $appState,
        ComposerInformation $composerInformation,
        $writers
    ) {
        $this->storeManager = $storeManager;
        $this->appState = $appState;
        $this->writers = $writers;
        $this->composerInformation = $composerInformation;
    }

    /**
     * @return DateTime
     */
    public function getNow(): DateTime
    {
        if (!$this->now) {
            $this->now = new DateTime();
        }
        return $this->now;
    }

    /**
     * @param DateTime $now
     */
    public function setNow(DateTime $now): void
    {
        $this->now = $now;
    }

    /**
     * @param WriterInterface[] $writers
     * @return void
     */
    public function setWriters($writers): void
    {
        $this->writers = [];
        foreach ($writers as $writer) {
            $this->addWriter($writer);
        }
    }

    /**
     * @param WriterInterface $writer
     * @return void
     */
    public function addWriter(WriterInterface $writer): void
    {
        $this->writers[] = $writer;
    }

    /**
     * @param resource $resource
     */
    public function write($resource): void
    {
        try {
            Profiler::start('write');
            $this->resource = $resource;
            $this->startDocument();
            $xml = $this->getXml();
            foreach ($this->writers as $writer) {
                $writer->write($this, $xml);
            }
            $this->endDocument();
        } finally {
            $this->close();
            Profiler::stop('write');
        }
    }

    /**
     * @return XMLWriter
     */
    protected function getXml(): XMLWriter
    {
        if (!$this->xml) {
            $xml = new XMLWriter();
            $xml->openMemory();
            if ($this->appState->getMode() === AppState::MODE_DEVELOPER) {
                $xml->setIndent(true);
                $xml->setIndentString('    ');
            } else {
                $xml->setIndent(false);
            }

            $this->xml = $xml;
        }

        return $this->xml;
    }

    /**
     * Close xml and writer references
     */
    protected function close(): void
    {
        $this->xml = null;
        $this->resource = null;
    }

    /**
     * Close XML writer
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Flush current content of writer to resource
     */
    public function flush(): void
    {
        $output = $this->getXml()->flush();
        if ($output) {
            fwrite($this->resource, $output);
        }
    }

    /**
     * Write document start
     */
    protected function startDocument(): void
    {
        $xml = $this->getXml();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('tweakwise'); // Start root
        $xml->writeElement('shop', $this->storeManager->getDefaultStoreView()->getName());
        $xml->writeElement('timestamp', $this->getNow()->format('Y-m-d\TH:i:s.uP'));
        $xml->writeElement('generatedby', $this->getModuleVersion());
        $this->flush();
    }

    /**
     * @return string
     */
    protected function getModuleVersion(): string
    {
        $installedPackages = $this->composerInformation
            ->getInstalledMagentoPackages();
        if (!isset($installedPackages['emico/tweakwise-export']['version'])) {
            // This should never be the case
            return '';
        }
        $version = $installedPackages['emico/tweakwise-export']['version'];

        return sprintf('Magento2TweakwiseExport %s', $version);
    }

    /**
     * Write document end
     */
    protected function endDocument(): void
    {
        $xml = $this->getXml();
        $xml->endElement(); // </tweakwise>
        $xml->endDocument();
        $this->flush();
    }
}
