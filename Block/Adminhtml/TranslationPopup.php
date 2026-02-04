<?php

namespace MageOS\AutomaticTranslation\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Framework\Serialize\Serializer\Json;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service;

/**
 * Class TranslationPopup
 * @package MageOS\AutomaticTranslation\Block\Adminhtml
 */
class TranslationPopup extends Template
{
    /**
     * @var ModuleConfig
     */
    private ModuleConfig $config;

    /**
     * @var Service
     */
    private Service $service;

    /**
     * @var Json
     */
    private Json $json;

    /**
     * TranslationPopup constructor.
     * @param Template\Context $context
     * @param ModuleConfig $config
     * @param Service $service
     * @param Json $json
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        ModuleConfig $config,
        Service $service,
        Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->service = $service;
        $this->json = $json;
    }

    /**
     * @inheritdoc
     * @since 100.4.0
     */
    public function getJsLayout()
    {
        $layout = $this->json->unserialize(parent::getJsLayout());

        $layout['components']['mageos-translation-popup']['title'] = __('Translate') . ' ' . __(
                $layout['components']['mageos-translation-popup']['config']['type']
            );
        $layout['components']['mageos-translation-popup']['content'] = __(
            'Choose the target translation language and the fields to translate.'
        );

        $layout['components']['mageos-translation-popup']['languages'] = $this->getAllowedLanguages();
        $layout['components']['mageos-translation-popup']['fields'] = $layout['components']['mageos-translation-popup']['config']['allowedFields'];
        $layout['components']['mageos-translation-popup']['scope'] = $layout['components']['mageos-translation-popup']['config']['scope'];

        return $this->json->serialize($layout);
    }

    /**
     * @inheritdoc
     * @since 100.4.0
     */
    public function toHtml()
    {
        if (!$this->config->isEnable()) {
            return '';
        }
        return parent::toHtml();
    }

    /**
     * @return array
     */
    private function getAllowedLanguages(): array
    {
        $result = [];
        foreach ($this->service->getStoresLanguages() as $language) {
            $result[] = [
                "value" => $language,
                "label" => $language
            ];
        }
        return $result;
    }
}
