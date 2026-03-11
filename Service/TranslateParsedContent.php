<?php

namespace MageOS\AutomaticTranslation\Service;

use MageOS\AutomaticTranslation\Helper\Service;
use MageOS\AutomaticTranslation\Model\Translator;

class TranslateParsedContent
{
    const array TRANSLATABLE_WIDGET_PARAMS = ['anchor_text', 'title', 'description'];

    protected Service $serviceHelper;
    protected Translator $translator;

    /**
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

        $widgetMap = [];
        $requestPostValue = preg_replace_callback(
            '/\{\{widget\s[^}]*\}\}/',
            function ($matches) use (&$widgetMap) {
                $key = 'WIDGET_PLACEHOLDER_' . count($widgetMap);
                $widgetMap[$key] = $matches[0];
                return $key;
            },
            $requestPostValue
        ) ?? $requestPostValue;

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

        foreach ($widgetMap as $key => $widget) {
            $result = str_replace(
                $key,
                $this->translateWidgetParams($widget, $destinationLanguage),
                $result
            );
        }

        foreach ($contentSettingsMap as $key => $value) {
            $result = str_replace(
                'content_settings="' . $key . '"',
                'content_settings="' . $value . '"',
                $result
            );
        }

        return $result;
    }

    /**
     * @param string $widget
     * @param string $destinationLanguage
     * @return string
     */
    protected function translateWidgetParams(
        string $widget,
        string $destinationLanguage
    ): string {
        foreach (self::TRANSLATABLE_WIDGET_PARAMS as $param) {
            $widget = preg_replace_callback(
                '/(' . preg_quote($param, '/') . '=")([^"]*)(")/',
                function ($matches) use ($destinationLanguage) {
                    if (trim($matches[2]) === '') {
                        return $matches[0];
                    }
                    return $matches[1]
                        . $this->translator->translate($matches[2], $destinationLanguage)
                        . $matches[3];
                },
                $widget
            ) ?? $widget;
        }

        return $widget;
    }
}
