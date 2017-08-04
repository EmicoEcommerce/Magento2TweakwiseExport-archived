<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Model\Write;

use DateTime;
use Emico\TweakwiseExport\Exception\WriteException;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Profiler;
use Magento\Store\Model\StoreManager;

class Writer
{
    /**
     * @var XMLWriter
     */
    protected $xml;

    /**
     * Resource where XML is written to after each flush
     *
     * @var Resource
     */
    protected $resource;

    /**
     * @var StoreManager
     */
    protected $storeManager;

    /**
     * @var AppState
     */
    protected $appState;

    /**
     * @var WriterInterface[]
     */
    protected $writers;

    /**
     * @var DateTime
     */
    protected $now;

    /**
     * Writer constructor.
     *
     * @param StoreManager $storeManager
     * @param AppState $appState
     * @param WriterInterface[] $writers
     */
    public function __construct(StoreManager $storeManager, AppState $appState, $writers)
    {
        $this->storeManager = $storeManager;
        $this->appState = $appState;
        $this->writers = $writers;
    }

    /**
     * @return DateTime
     */
    public function getNow()
    {
        if (!$this->now) {
            $this->now = new DateTime();
        }
        return $this->now;
    }

    /**
     * @param DateTime $now
     * @return $this
     */
    public function setNow(DateTime $now)
    {
        $this->now = $now;
        return $this;
    }

    /**
     * @param WriterInterface[] $writers
     * @return $this
     */
    public function setWriters($writers)
    {
        $this->writers = [];
        foreach ($writers as $writer) {
            $this->addWriter($writer);
        }
        return $this;
    }

    /**
     * @param WriterInterface $writer
     * @return $this
     */
    public function addWriter(WriterInterface $writer)
    {
        $this->writers[] = $writer;
        return $this;
    }

    /**
     * @param resource $resource
     * @throws WriteException
     */
    public function write($resource)
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
    protected function getXml()
    {
        if (!$this->xml) {
            $xml = new XMLWriter();
            $xml->openMemory();
            if ($this->appState->getMode() == AppState::MODE_DEVELOPER) {
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
     *
     * @return $this
     */
    protected function close()
    {
        $this->xml = null;
        $this->resource = null;
        return $this;
    }

    /**
     * Close XML writer
     */
    function __destruct()
    {
        $this->close();
    }

    /**
     * Flush current content of writer to resource
     * @return $this
     */
    public function flush()
    {
        $output = $this->getXml()->flush();
        fwrite($this->resource, $output);
        return $this;
    }

    /**
     * Write document start
     *
     * @return $this
     */
    protected function startDocument()
    {
        $xml = $this->getXml();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement('tweakwise'); // Start root
        $xml->writeElement('shop', $this->storeManager->getDefaultStoreView()->getName());
        $xml->writeElement('timestamp', $this->getNow()->format('Y-m-d\TH:i:s.uP'));
        $this->flush();
        return $this;
    }

    /**
     * Write document end
     * @return $this
     */
    protected function endDocument()
    {
        $xml = $this->getXml();
        $xml->endElement(); // </tweakwise>
        $xml->endDocument();
        $this->flush();
        return $this;
    }
}
