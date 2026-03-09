<?php

namespace MageOS\AutomaticTranslation\Service;

use Magento\Framework\Message\ManagerInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Helper\Service;
use MageOS\AutomaticTranslation\Model\Translator;
use Psr\Log\LoggerInterface as Logger;

/**
 * Class TranslateParsedContent
 * @package MageOS\AutomaticTranslation\Service
 */
class TranslateParsedContent
{
    /**
     * @var Service
     */
    protected Service $serviceHelper;

    /**
     * @var Translator
     */
    protected Translator $translator;

    /**
     * TranslateParsedContent constructor.
     * @param Service $serviceHelper
     * @param Translator $translator
     */
    public function __construct(
        Service $serviceHelper,
        Translator $translator,
    ) {
        $this->serviceHelper = $serviceHelper;
        $this->translator = $translator;
    }

    /**
     * @param mixed $parsedContent
     * @param string $requestPostValue
     * @param string $destinationLanguage
     * @return mixed|string
     */
    public function execute($parsedContent, string $requestPostValue, string $destinationLanguage)
    {
        if (is_string($parsedContent)) {
            return $this->translator->translate(
                $parsedContent,
                $destinationLanguage
            );
        }

        $requestPostValue = html_entity_decode(htmlspecialchars_decode($requestPostValue));

        foreach ($parsedContent as $parsedString) {
            $parsedString["translation"] = $this->translator->translate(
                $parsedString["source"],
                $destinationLanguage
            );

            $requestPostValue = str_replace(
                $parsedString["source"],
                $parsedString["translation"],
                $requestPostValue
            );
        }

        return $this->serviceHelper->encodePageBuilderHtmlBox($requestPostValue);
    }
}
