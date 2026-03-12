<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Framework\Serialize\Serializer\Json;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service;
use InvalidArgumentException;

class TranslationPopup extends Template
{
    /**
     * @param Template\Context $context
     * @param ModuleConfig $config
     * @param Service $service
     * @param Json $json
     * @param array $data
     */
    public function __construct(
        Template\Context $context,
        protected ModuleConfig $config,
        protected Service $service,
        protected Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * @return string
     * @throws InvalidArgumentException
     */
    public function getJsLayout(): string
    {
        $layout = $this->json->unserialize(parent::getJsLayout());
        $popup = &$layout['components']['mageos-translation-popup'];

        $popup['title'] = __('Translate') . ' ' . __($popup['config']['type']);
        $popup['content'] = __('Choose the target translation language and the fields to translate.');
        $popup['languages'] = $this->getAllowedLanguages();
        $popup['fields'] = $popup['config']['allowedFields'];
        $popup['scope'] = $popup['config']['scope'];

        return $this->json->serialize($layout);
    }

    /**
     * @return string
     */
    public function toHtml(): string
    {
        if (!$this->config->isEnable()) {
            return '';
        }
        return parent::toHtml();
    }

    /**
     * @return array
     */
    protected function getAllowedLanguages(): array
    {
        return array_map(
            fn($language) => ['value' => $language, 'label' => $language],
            $this->service->getStoresLanguages()
        );
    }
}
