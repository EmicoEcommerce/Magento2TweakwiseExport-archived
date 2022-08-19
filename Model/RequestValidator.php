<?php

namespace Tweakwise\Magento2TweakwiseExport\Model;

use Magento\Framework\App\RequestInterface;
use Tweakwise\Magento2TweakwiseExport\Model\Config;

class RequestValidator
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * RequestValidator constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param RequestInterface $request
     * @return bool
     */
    public function validateRequestKey(RequestInterface $request): bool
    {
        $key = $this->config->getKey();
        $requestKey = $request->getParam('key');

        return $key === $requestKey;
    }
}
