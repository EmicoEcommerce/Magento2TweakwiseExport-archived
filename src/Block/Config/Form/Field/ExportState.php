<?php
/**
 * @author Emico <info@emico.nl>
 * @copyright (c) Emico B.V. 2017
 */

namespace Emico\TweakwiseExport\Block\Config\Form\Field;

use Emico\TweakwiseExport\Model\Helper;
use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class ExportState extends Field
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @param Context $context
     * @param array $data
     */
    public function __construct(Context $context, Helper $helper, array $data = []) {
        parent::__construct($context, $data);
        $this->helper = $helper;
    }

    /**
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->helper->getExportStateText();
    }
}