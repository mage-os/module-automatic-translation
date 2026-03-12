<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use OpenAI;
use OpenAI\Client as OpenAIClient;
use Exception;

class GPTModels implements OptionSourceInterface
{
    protected ?OpenAIClient $openAIclient = null;

    /**
     * @param OpenAI $openAI
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        protected OpenAI $openAI,
        protected ModuleConfig $moduleConfig
    ) {
    }

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        $optionArray = [['value' => '', 'label' => __('-- Please Select --')]];

        if (empty($this->moduleConfig->getOpenAIOrgID()) || empty($this->moduleConfig->getOpenAIApiKey())) {
            return $optionArray;
        }

        try {
            $this->openAIclient ??= $this->openAI::client(
                $this->moduleConfig->getOpenAIApiKey(),
                $this->moduleConfig->getOpenAIOrgID(),
                $this->moduleConfig->getOpenAIProjectID() ?: null
            );

            $models = $this->openAIclient->models()->list()->toArray();

            return array_merge($optionArray, array_map(
                fn($model) => ['value' => $model['id'], 'label' => $model['id']],
                $models['data']
            ));
        } catch (Exception) {
            return $optionArray;
        }
    }
}
