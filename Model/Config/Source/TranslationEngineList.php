<?php

namespace MageOS\AutomaticTranslation\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;

class TranslationEngineList implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => '',
                'label' => __('-- Please Select --')
            ],
            [
                'value' => \MageOS\AutomaticTranslation\Model\Translator\DeepL::class,
                'label' => __('DeepL (recommended)')
            ],
            [
                'value' => \MageOS\AutomaticTranslation\Model\Translator\OpenAI::class,
                'label' => __('OpenAI (GPT-3, GTP-4, ChatGPT, ecc..)')
            ]
        ];
    }
}
