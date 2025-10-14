<?php

namespace MageOS\AutomaticTranslation\Block\Adminhtml\Product;

use Magento\Catalog\Block\Adminhtml\Product\Edit\Button\Generic;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\UiComponent\Context;
use MageOS\AutomaticTranslation\Block\Adminhtml\Component\Control\SplitButton;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;

/**
 * Class TranslateButton
 * @package MageOS\AutomaticTranslation\Block\Adminhtml\Product
 */
class TranslateButton extends Generic
{

    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;

    /**
     * TranslateButton constructor.
     * @param Context $context
     * @param Registry $registry
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ModuleConfig $moduleConfig
    ) {
        $this->moduleConfig = $moduleConfig;
        parent::__construct($context, $registry);
    }

    /**
     * @return array
     */
    public function getButtonData(): array
    {
        $currentStore = $this->context->getRequestParam("store");
        if ($currentStore && (int)$currentStore !== 0) {
            if ($this->moduleConfig->isEnable((int)$currentStore)) {
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
                                            [
                                                'back' => 'edit',
                                                'translate' => true
                                            ]
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
                            'data_attribute' => [
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
                            ]
                        ]
                    ],
                    'dropdown_button_aria_label' => __('Save options'),
                    'sort_order' => 100
                ];
            } else {
                return [];
            }
        }

        if ($this->moduleConfig->isEnable()) {
            return [
                'label' => __('Translate'),
                'class' => 'save action-secondary',
                'data_attribute' => [
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
                ],
                'on_click' => '',
                'sort_order' => 100
            ];
        }
        return [];
    }

    /**
     * Retrieve target for button.
     *
     * @return string
     */
    protected function getSaveTarget(): string
    {
        $target = 'product_form.product_form';
        if ($this->isConfigurableProduct()) {
            $target = 'product_form.product_form.configurableVariations';
        }
        return $target;
    }

    /**
     * Retrieve action for button.
     *
     * @return string
     */
    protected function getSaveAction(): string
    {
        $action = 'save';
        if ($this->isConfigurableProduct()) {
            $action = 'saveFormHandler';
        }
        return $action;
    }

    /**
     * Is configurable product.
     *
     * @return bool
     */
    protected function isConfigurableProduct(): bool
    {
        return !$this->getProduct()->isComposite() || $this->getProduct()->getTypeId() === ConfigurableType::TYPE_CODE;
    }
}
