<?php

namespace MageOS\AutomaticTranslation\Block\Adminhtml\Component\Control;


class SplitButton extends \Magento\Ui\Component\Control\SplitButton
{

    /**
     * Retrieve button attributes html
     *
     * @return string
     */
    public function getButtonAttributesHtml()
    {
        $disabled = $this->getDisabled() ? 'disabled' : '';
        $classes = ['action-default', 'secondary'];

        if (!($title = $this->getTitle())) {
            $title = $this->getLabel();
        }

        if ($this->getButtonClass()) {
            $classes[] = $this->getButtonClass();
        }

        if ($disabled) {
            $classes[] = $disabled;
        }

        $attributes = [
            'id' => $this->getButtonId(),
            'title' => $title,
            'class' => join(' ', $classes),
            'disabled' => $disabled,
        ];

        if ($idHard = $this->getIdHard()) {
            $attributes['id'] = $idHard;
        }

        //TODO perhaps we need to skip data-mage-init when disabled="disabled"
        if ($dataAttribute = $this->getDataAttribute()) {
            $this->getDataAttributes($dataAttribute, $attributes);
        }

        $html = $this->attributesToHtml($attributes);
        $html .= $this->getUiId();

        return $html;
    }

    /**
     * Retrieve toggle button attributes html
     *
     * @return string
     */
    public function getToggleAttributesHtml()
    {
        $disabled = $this->getDisabled() ? 'disabled' : '';
        $classes = ['action-toggle', 'secondary'];

        if (!($title = $this->getTitle())) {
            $title = $this->getLabel();
        }

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
