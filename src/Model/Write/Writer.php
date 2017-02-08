<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   Proprietary and confidential, Unauthorized copying of this file, via any medium is strictly prohibited
 */

namespace Emico\TweakwiseExport\Model\Write;

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
     * Writer constructor.
     *
     * @param StoreManager $storeManager
     * @param AppState $appState
     */
    public function __construct(StoreManager $storeManager, AppState $appState)
    {
        $this->storeManager = $storeManager;
        $this->appState = $appState;
    }

    /**
     * @param resource $resource
     * @throws WriteException
     */
    public function write($resource)
    {
        try {
            Profiler::start('tweakwise::export::write');
            $this->resource = $resource;
            $this->startDocument();
            $this->endDocument();
        } finally {
            $this->close();
            Profiler::stop('tweakwise::export::write');
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
    protected function flush()
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
        $xml->writeElement('timestamp', date('Y-m-d\TH:i:s.uP'));
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
