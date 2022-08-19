<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2TweakwiseExport\Controller\Feed;

use Tweakwise\Magento2TweakwiseExport\App\Response\FeedContent;
use Tweakwise\Magento2TweakwiseExport\Model\Export as ExportModel;
use Tweakwise\Magento2TweakwiseExport\Model\Logger;
use Tweakwise\Magento2TweakwiseExport\Model\RequestValidator;
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
    protected $export;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    /**
     * @var Context
     */
    protected $context;

    /**
     * @var RequestValidator
     */
    protected $requestValidator;

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
