<?php
/**
 * @author : Edwin Jacobs, email: ejacobs@emico.nl.
 * @copyright : Copyright Emico B.V. 2019.
 */

namespace Emico\TweakwiseExport\Model;

use Magento\Framework\App\RequestInterface;
use Emico\TweakwiseExport\Model\Config;

class RequestValidator
{
    /**
     * @var Config
     */
    protected \Emico\TweakwiseExport\Model\Config $config;

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
