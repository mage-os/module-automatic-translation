<?php

namespace MageOS\AutomaticTranslation\Model\Config\Source;

use Exception;
use Magento\Framework\Data\OptionSourceInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use OpenAI;
use OpenAI\Client as OpenAIClient;

/**
 * Class GPTModels
 */
class GPTModels implements OptionSourceInterface
{
    /**
     * @var OpenAIClient|null
     */
    protected ?OpenAIClient $openAIclient = null;
    /**
     * @var OpenAI
     */
    protected OpenAI $openAI;
    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;

    /**
     * GPTModels constructor.
     * @param OpenAI $openAI
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        OpenAI $openAI,
        ModuleConfig $moduleConfig
    ) {
        $this->openAI = $openAI;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * @return void
     */
    protected function initClient(): void
    {
        $apiKey = $this->moduleConfig->getOpenAIApiKey();
        $organization = $this->moduleConfig->getOpenAIOrgID();
        $projectId = $this->moduleConfig->getOpenAIProjectID();

        $this->openAIclient = $this->openAI::client($apiKey, $organization, !empty($projectId) ? $projectId : null);
    }

    /**
     * @return array[]
     */
    public function toOptionArray(): array
    {
        $optionArray = [['value' => '', 'label' => __('-- Please Select --')]];

        if (!empty($this->moduleConfig->getOpenAIOrgID()) && !empty($this->moduleConfig->getOpenAIApiKey())) {
            try {
                if (empty($this->openAIclient)) {
                    $this->initClient();
                }

                $models = $this->openAIclient->models()->list()->toArray();

                foreach ($models['data'] as $model) {
                    $optionArray[] = [
                        'value' => $model['id'],
                        'label' => $model['id']
                    ];
                }
            } catch (Exception $e) {
                return $optionArray;
            }
        }

        return $optionArray;
    }
}
