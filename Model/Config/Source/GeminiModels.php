<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Model\Config\Source;

use Gemini;
use Gemini\Client as GeminiClient;
use Magento\Framework\Data\OptionSourceInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use Exception;

class GeminiModels implements OptionSourceInterface
{
    protected ?GeminiClient $geminiClient = null;

    /**
     * @param Gemini $gemini
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        protected Gemini $gemini,
        protected ModuleConfig $moduleConfig
    ) {
    }

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        $optionArray = [['value' => '', 'label' => __('-- Please Select --')]];

        if (empty($this->moduleConfig->getGeminiApiKey())) {
            return $optionArray;
        }

        try {
            $this->geminiClient ??= $this->gemini::client($this->moduleConfig->getGeminiApiKey());

            $models = $this->geminiClient->models()->list()->toArray();

            return array_merge($optionArray, array_map(
                fn($model) => ['value' => $model['name'], 'label' => $model['displayName']],
                $models['models']
            ));
        } catch (Exception) {
            return $optionArray;
        }
    }
}
