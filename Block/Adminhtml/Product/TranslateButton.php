<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Block\Adminhtml\Product;

use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Context;
use MageOS\AutomaticTranslation\Block\Adminhtml\Component\Control\SplitButton;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;

class TranslateButton extends Generic
{
    /**
     * @param Context $context
     * @param Registry $registry
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        Context $context,
        Registry $registry,
        protected ModuleConfig $moduleConfig
    ) {
        parent::__construct($context, $registry);
    }

    /**
     * @return array
     */
    public function getButtonData(): array
    {
        $currentStore = (int)$this->context->getRequestParam("store");

        if (!$this->moduleConfig->isEnable($currentStore ?: 0)) {
            return [];
        }

        if ($currentStore !== 0) {
            return [
                'label' => __('Translate'),
                'class' => 'save secondary',
                'data_attribute' => [
                    'mage-init' => [
                        'buttonAdapter' => [
                            'actions' => [
                                [
                                    'targetName' => $this->getSaveTarget(),
                                    'actionName' => $this->getSaveAction(),
                                    'params' => [
                                        true,
                                        ['back' => 'edit', 'translate' => true]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'class_name' => SplitButton::class,
                'options' => [
                    [
                        'label' => __('Switch translation scope'),
                        'data_attribute' => $this->getStoreModalDataAttribute()
                    ]
                ],
                'dropdown_button_aria_label' => __('Save options'),
                'sort_order' => 100
            ];
        }

        return [
            'label' => __('Translate'),
            'class' => 'save action-secondary',
            'data_attribute' => $this->getStoreModalDataAttribute(),
            'on_click' => '',
            'sort_order' => 100
        ];
    }

    /**
     * @return array
     */
    protected function getStoreModalDataAttribute(): array
    {
        return [
            'mage-init' => [
                'buttonAdapter' => [
                    'actions' => [
                        [
                            'targetName' => 'product_form.product_form.select_store_modal',
                            'actionName' => 'toggleModal'
                        ],
                        [
                            'targetName' => 'product_form.product_form.select_store_modal.translation_store_list',
                            'actionName' => 'render'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * @return string
     */
    protected function getSaveTarget(): string
    {
        return $this->isConfigurableProduct()
            ? 'product_form.product_form.configurableVariations'
            : 'product_form.product_form';
    }

    /**
     * @return string
     */
    protected function getSaveAction(): string
    {
        return $this->isConfigurableProduct() ? 'saveFormHandler' : 'save';
    }

    /**
     * @return bool
     */
    protected function isConfigurableProduct(): bool
    {
        return !$this->getProduct()->isComposite() || $this->getProduct()->getTypeId() === ConfigurableType::TYPE_CODE;
    }
}
