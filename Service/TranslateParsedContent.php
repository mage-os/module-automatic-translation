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

        $contentSettingsMap = [];
        $requestPostValue = preg_replace_callback(
            '/content_settings="([^"]*)"/',
            function ($matches) use (&$contentSettingsMap) {
                $key = 'CS_PLACEHOLDER_' . count($contentSettingsMap);
                $contentSettingsMap[$key] = $matches[1];
                return 'content_settings="' . $key . '"';
            },
            $requestPostValue
        ) ?? $requestPostValue;

        $requestPostValue = html_entity_decode(htmlspecialchars_decode($requestPostValue));

        foreach ($parsedContent as $parsedString) {
            $parsedString["translation"] = $this->translator->translate(
                $parsedString["source"],
                $destinationLanguage
            );

            $pos = strpos($requestPostValue, $parsedString["source"]);

            if ($pos !== false) {
                $requestPostValue = substr_replace(
                    $requestPostValue,
                    $parsedString["translation"],
                    $pos,
                    strlen($parsedString["source"])
                );
            }
        }

        $result = $this->serviceHelper->encodePageBuilderHtmlBox($requestPostValue);

        foreach ($contentSettingsMap as $key => $value) {
            $result = str_replace(
                'content_settings="' . $key . '"',
                'content_settings="' . $value . '"',
                $result
            );
        }

        return $result;
    }
}
