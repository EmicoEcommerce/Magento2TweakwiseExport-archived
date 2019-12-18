<?php
/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2019.
 */

namespace Emico\TweakwiseExport\Controller\Cache;

use Emico\TweakwiseExport\Model\CacheHandler;
use Emico\TweakwiseExport\Model\Config;
use Emico\TweakwiseExport\Model\RequestValidator;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Response\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\App\Response\HttpFactory as ResponseFactory;

/**
 * Class Flush, handle cache flush request from tweakwise platform
 *
 * @package Emico\TweakwiseExport\Controller\Cache
 */
class Flush implements ActionInterface
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var RequestValidator
     */
    protected $requestValidator;

    /**
     * @var ResponseFactory
     */
    protected $responseFactory;

    /**
     * @var CacheHandler
     */
    protected $cacheHandler;

    /**
     * Flush constructor.
     *
     * @param Context $context
     * @param Config $config
     * @param RequestValidator $requestValidator
     * @param ResponseFactory $responseFactory
     * @param CacheHandler $cacheHandler
     */
    public function __construct(
        Context $context,
        Config $config,
        RequestValidator $requestValidator,
        ResponseFactory $responseFactory,
        CacheHandler $cacheHandler
    ) {
        $this->context = $context;
        $this->requestValidator = $requestValidator;
        $this->config = $config;
        $this->responseFactory = $responseFactory;
        $this->cacheHandler = $cacheHandler;
    }

    /**
     * Execute action based on request and return result
     *
     * Note: Request will be added as operation argument in future
     *
     * @return ResponseInterface
     * @throws NotFoundException
     */
    public function execute(): ResponseInterface
    {
        if (!$this->requestValidator->validateRequestKey($this->context->getRequest())) {
            throw new NotFoundException(__('Page not found.'));
        }

        if (!$this->config->isAllowCacheFlush()) {
            return $this->createOkResponse('Cache flush not enabled in settings');
        }
        // Clear caches
        $this->cacheHandler->clear();

        return $this->createOkResponse('Caches have been flushed');
    }

    /**
     * @param string $body Message
     * @return Http
     */
    protected function createOkResponse(string $body): Http
    {
        $response = $this->responseFactory->create();
        $response->setNoCacheHeaders();
        $response->setStatusCode(200);
        $response->setBody($body);

        return $response;
    }
}
