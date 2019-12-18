<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Controller\Feed;

use Emico\TweakwiseExport\App\Response\FeedContent;
use Emico\TweakwiseExport\Model\Export as ExportModel;
use Emico\TweakwiseExport\Model\Logger;
use Emico\TweakwiseExport\Model\RequestValidator;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\MediaStorage\Model\File\Storage\Response;
use Magento\MediaStorage\Model\File\Storage\ResponseFactory;

class Export implements ActionInterface
{
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
     * @var RequestValidator
     */
    private $requestValidator;

    /**
     * Export constructor.
     *
     * @param Context $context
     * @param ExportModel $export
     * @param Logger $log
     * @param RequestValidator $requestValidator
     * @param ResponseFactory $responseFactory
     */
    public function __construct(
        Context $context,
        ExportModel $export,
        Logger $log,
        RequestValidator $requestValidator,
        ResponseFactory $responseFactory
    ) {
        $this->context = $context;
        $this->export = $export;
        $this->log = $log;
        $this->requestValidator = $requestValidator;
        $this->responseFactory = $responseFactory;
    }

    /**
     * We return an instance of NotCacheableInterface
     * to make sure that sendVary does not get triggered
     * as that would result in a "headers already sent exception"
     *
     * @see    \Magento\Framework\App\PageCache\NotCacheableInterface
     * @see    \Magento\PageCache\Model\App\Response\HttpPlugin
     * @see    \Magento\MediaStorage\Model\File\Storage\Response
     * @throws NotFoundException
     * @return Response
     */
    public function execute(): Response
    {
        if (!$this->requestValidator->validateRequestKey($this->context->getRequest())) {
            throw new NotFoundException(__('Page not found.'));
        }

        (new FeedContent($this->export, $this->log))->__toString();

        return $this->responseFactory->create()
            ->setHeader('Cache-Control', 'no-cache');
    }
}
