<?php
/**
 * Tweakwise & Emico (https://www.tweakwise.com/ & https://www.emico.nl/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2017 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Emico\TweakwiseExport\Controller\Adminhtml\Export;

use Emico\TweakwiseExport\Model\Scheduler;
use Exception;
use InvalidArgumentException;
use Magento\Backend\App\Action;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;

class Trigger extends Action
{
    /**
     * @var Scheduler
     */
    protected Scheduler $scheduler;

    /**
     * Trigger constructor.
     * @param Action\Context $context
     * @param Scheduler $scheduler
     */
    public function __construct(Action\Context $context, Scheduler $scheduler)
    {
        parent::__construct($context);
        $this->scheduler = $scheduler;
    }

    /**
     * Schedule new export
     *
     * @return ResultInterface
     * @throws InvalidArgumentException
     */
    public function execute()
    {
        try {
            $this->scheduler->schedule();
            $this->messageManager->addSuccessMessage('Scheduled new TweakwiseExport');
        } catch (Exception $e) {
            $this->messageManager->addErrorMessage('Failed creating Tweakwise job');
        }

        return $this->createRefererRedirect();
    }

    /**
     * @return ResultInterface
     * @throws InvalidArgumentException
     */
    protected function createRefererRedirect()
    {
        /** @var Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $redirectUrl = $this->_redirect->getRefererUrl();
        if (!$redirectUrl) {
            $redirectUrl = $this->_url->getUrl('adminhtml');
        }
        $resultRedirect->setUrl($redirectUrl);

        return $resultRedirect;
    }
}
