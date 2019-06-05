<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Controller\Feed;

use Emico\TweakwiseExport\App\Response\FeedContent;
use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\Export as ExportModel;
use Emico\TweakwiseExport\Model\Logger;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\MediaStorage\Model\File\Storage\ResponseFactory;

class Export implements ActionInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Export
     */
    private $export;

    /**
     * @var Logger
     */
    private $log;

    /**
     * @var ResponseFactory
     */
    private $responseFactory;

    /**
     * @var Context
     */
    private $context;

    /**
     * Export constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param ExportModel $export
     * @param Logger $log
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        Context $context,
        Config $config,
        ExportModel $export,
        Logger $log,
        ResponseFactory $responseFactory
    ) {
        $this->config = $config;
        $this->export = $export;
        $this->log = $log;
        $this->responseFactory = $responseFactory;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        $requestKey = $this->context->getRequest()->getParam('key');
        $configKey = $this->config->getKey();
        if ($requestKey !== $configKey) {
            throw new NotFoundException(__('Page not found.'));
        }

        (new FeedContent($this->export, $this->log))->__toString();

        return $this->responseFactory->create()
            ->setHeader('Cache-Control', 'no-cache');
    }
}
