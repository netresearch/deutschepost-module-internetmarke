<?php

/**
 * See LICENSE.md for license details.
 */

declare(strict_types=1);

namespace DeutschePost\Internetmarke\Block\Adminhtml\System\Config\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;

class SetupActions extends Field
{
    /**
     * Unset some non-related element parameters
     *
     * @param AbstractElement $element
     * @return string
     */
    public function render(AbstractElement $element): string
    {
        $element->unsetData('scope')->unsetData('can_use_website_value')->unsetData('can_use_default_value');
        return parent::render($element);
    }

    protected function _getElementHtml(AbstractElement $element)
    {
        $fieldContent = $this->getBlockHtml('deutschepost_internetmarke_setup_actions');
        $element->setData('text', $fieldContent);
        return parent::_getElementHtml($element);
    }
}
