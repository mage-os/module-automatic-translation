<?php

namespace MageOS\AutomaticTranslation\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Gemini;
use Gemini\Client as GeminiClient;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use Exception;

/**
 * Class GeminiModels
 */
class GeminiModels implements OptionSourceInterface
{
    /**
     * @var GeminiClient|null
     */
    protected ?GeminiClient $geminiClient = null;
    /**
     * @var Gemini
     */
    protected Gemini $gemini;
    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;

    /**
     * GeminiModels constructor.
     * @param Gemini $gemini
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        Gemini $gemini,
        ModuleConfig $moduleConfig
    ) {
        $this->gemini = $gemini;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * @return void
     */
    protected function initClient()
    {
        $apiKey = $this->moduleConfig->getGeminiApiKey();
        $this->geminiClient = $this->gemini::client($apiKey);
    }

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        $optionArray = [['value' => '', 'label' => __('-- Please Select --')]];

        if (!empty($this->moduleConfig->getGeminiApiKey())) {
            try {
                if (empty($this->geminiClient)) {
                    $this->initClient();
                }

                $models = $this->geminiClient->models()->list()->toArray();

                foreach ($models['models'] as $model) {
                    $optionArray[] = [
                        'value' => $model['name'],
                        'label' => $model['displayName']
                    ];
                }
            } catch (Exception $e) {
                return $optionArray;
            }

        }

        return $optionArray;
    }
}
