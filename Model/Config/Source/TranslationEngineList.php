<?php

namespace MageOS\AutomaticTranslation\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use MageOS\AutomaticTranslation\Model\Translator\DeepL;
use MageOS\AutomaticTranslation\Model\Translator\GoogleGemini;
use MageOS\AutomaticTranslation\Model\Translator\OpenAI;

/**
 * Class TranslationEngineList
 */
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
                'value' => DeepL::class,
                'label' => __('DeepL (recommended)')
            ],
            [
                'value' => OpenAI::class,
                'label' => __('OpenAI (GPT, ChatGPT, ecc..)')
            ],
            [
                'value' => GoogleGemini::class,
                'label' => __('Google Gemini')
            ]
        ];
    }
}
