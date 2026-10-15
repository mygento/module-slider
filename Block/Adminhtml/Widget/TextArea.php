<?php

/**
 * @author Mygento Team
 * @copyright 2026 Mygento (https://www.mygento.com)
 * @package Mygento_Slider
 */

namespace Mygento\Slider\Block\Adminhtml\Widget;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\Factory;

class TextArea extends Template
{
    public function __construct(
        private Factory $elementFactory,
        Context $context,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    public function prepareElementHtml(AbstractElement $element): AbstractElement
    {
        $input = $this->elementFactory->create('textarea', ['data' => $element->getData()]);
        $input->setId($element->getId());
        $input->setForm($element->getForm());
        $input->setClass('widget-option input-textarea admin__control-text');
        if ($element->getRequired()) {
            $input->addClass('required-entry');
        }

        $customStyle = '<style>.control-value {display: none}</style>';
        $element->setData('after_element_html', $customStyle . $input->getElementHtml());

        return $element;
    }
}
