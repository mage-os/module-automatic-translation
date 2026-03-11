<?php
declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Service;

use MageOS\AutomaticTranslation\Helper\Service;
use MageOS\AutomaticTranslation\Model\Translator;
use RuntimeException;
use Exception;

class TranslateParsedContent
{
    const TRANSLATABLE_WIDGET_PARAMS = ['anchor_text', 'title', 'description'];
    const CONTENT_SETTINGS_PATTERN = '/content_settings="[^"]*"/';
    const WIDGET_PATTERN = '/\{\{widget\s[^}]*\}\}/';

    /**
     * @param Service $serviceHelper
     * @param Translator $translator
     */
    public function __construct(
        protected Service $serviceHelper,
        protected Translator $translator,
    ) {
    }

    /**
     * @param mixed $parsedContent
     * @param string $requestPostValue
     * @param string $destinationLanguage
     * @return mixed
     * @throws RuntimeException
     * @throws Exception
     */
    public function execute(
        mixed $parsedContent,
        string $requestPostValue,
        string $destinationLanguage
    ): mixed {
        if (is_string($parsedContent)) {
            return $this->translator->translate($parsedContent, $destinationLanguage);
        }

        [$requestPostValue, $contentSettingsMap] = $this->extractPlaceholders(
            $requestPostValue,
            self::CONTENT_SETTINGS_PATTERN,
            'CS_PLACEHOLDER_'
        );

        $requestPostValue = html_entity_decode(htmlspecialchars_decode($requestPostValue));

        [$requestPostValue, $widgetMap] = $this->extractPlaceholders(
            $requestPostValue,
            self::WIDGET_PATTERN,
            'WIDGET_PLACEHOLDER_'
        );

        foreach ($parsedContent as $parsedString) {
            $pos = strpos($requestPostValue, $parsedString["source"]);

            if ($pos !== false) {
                $translation = $this->translator->translate(
                    $parsedString["source"],
                    $destinationLanguage
                );
                $requestPostValue = substr_replace(
                    $requestPostValue,
                    $translation,
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

        foreach ($contentSettingsMap as $key => $original) {
            $result = str_replace($key, $original, $result);
        }

        return $result;
    }

    /**
     * @param string $text
     * @param string $pattern
     * @param string $prefix
     * @return array{string, array<string, string>}
     * @throws RuntimeException
     */
    protected function extractPlaceholders(
        string $text,
        string $pattern,
        string $prefix
    ): array {
        $map = [];
        $result = preg_replace_callback(
            $pattern,
            function (array $matches) use (&$map, $prefix): string {
                $key = $prefix . count($map);
                $map[$key] = $matches[0];
                return $key;
            },
            $text
        );

        if ($result === null) {
            throw new RuntimeException(sprintf('preg_replace_callback failed with error %d', preg_last_error()));
        }

        return [$result, $map];
    }

    /**
     * @param string $widget
     * @param string $destinationLanguage
     * @return string
     * @throws RuntimeException
     * @throws Exception
     */
    protected function translateWidgetParams(
        string $widget,
        string $destinationLanguage
    ): string {
        foreach (self::TRANSLATABLE_WIDGET_PARAMS as $param) {
            $result = preg_replace_callback(
                '/(' . preg_quote($param, '/') . '=")([^"]*)(")/',
                function (array $matches) use ($destinationLanguage): string {
                    if (trim($matches[2]) === '') {
                        return $matches[0];
                    }
                    return $matches[1]
                        . $this->translator->translate($matches[2], $destinationLanguage)
                        . $matches[3];
                },
                $widget
            );

            if ($result === null) {
                throw new RuntimeException(sprintf('preg_replace_callback failed with error %d', preg_last_error()));
            }

            $widget = $result;
        }

        return $widget;
    }
}
