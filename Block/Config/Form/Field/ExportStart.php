<?php
/**
 * Tweakwise (https://www.tweakwise.com/) - All Rights Reserved
 *
 * @copyright Copyright (c) 2017-2022 Tweakwise.com B.V. (https://www.tweakwise.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Tweakwise\Magento2TweakwiseExport\Block\Config\Form\Field;

use Magento\Backend\Block\Widget\Button;
use Magento\Config\Block\System\Config\Form;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class ExportStart
 *
 * @package Tweakwise\Magento2TweakwiseExport\Block\Config\Form\Field
 *
 * @method Form getForm();
 */
class ExportStart extends Field
{
    /**
     * @param AbstractElement $element
     * @return string
     * @throws LocalizedException
     */
    protected function _getElementHtml(AbstractElement $element): string
    {
        /** @var Button $button  */
        $button = $this->getForm()->getLayout()->createBlock(Button::class);
        $button->setData([
            'label' => __('Schedule'),
            'onclick' => "setLocation('{$this->getUrl('tweakwise/export/trigger')}')",
        ]);

        return $button->toHtml();
    }
}
