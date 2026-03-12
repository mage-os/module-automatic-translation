<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Block\Adminhtml\Component\Control;

use Magento\Ui\Component\Control\SplitButton as BaseSplitButton;

class SplitButton extends BaseSplitButton
{
    /**
     * @return string
     */
    public function getButtonAttributesHtml(): string
    {
        $disabled = $this->getDisabled() ? 'disabled' : '';
        $classes = ['action-default', 'secondary'];

        $title = $this->getTitle() ?: $this->getLabel();

        if ($this->getButtonClass()) {
            $classes[] = $this->getButtonClass();
        }

        if ($disabled) {
            $classes[] = $disabled;
        }

        $attributes = [
            'id' => $this->getIdHard() ?: $this->getButtonId(),
            'title' => $title,
            'class' => join(' ', $classes),
            'disabled' => $disabled,
        ];

        if ($dataAttribute = $this->getDataAttribute()) {
            $this->getDataAttributes($dataAttribute, $attributes);
        }

        $html = $this->attributesToHtml($attributes);
        $html .= $this->getUiId();

        return $html;
    }

    /**
     * @return string
     */
    public function getToggleAttributesHtml(): string
    {
        $disabled = $this->getDisabled() ? 'disabled' : '';
        $classes = ['action-toggle', 'secondary'];

        $title = $this->getTitle() ?: $this->getLabel();

        if ($currentClass = $this->getClass()) {
            $classes[] = $currentClass;
        }

        if ($disabled) {
            $classes[] = $disabled;
        }

        $attributes = [
            'title' => $title,
            'class' => join(' ', $classes),
            'disabled' => $disabled,
            'aria-label' => (string)$this->getData('dropdown_button_aria_label'),
        ];
        $this->getDataAttributes(['mage-init' => '{"dropdown": {}}', 'toggle' => 'dropdown'], $attributes);

        $html = $this->attributesToHtml($attributes);
        $html .= $this->getUiId('dropdown');

        return $html;
    }
}
