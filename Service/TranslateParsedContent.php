<?php
declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Service;

use MageOS\AutomaticTranslation\Helper\Service;
use MageOS\AutomaticTranslation\Model\Translator;
use Magento\Framework\Serialize\Serializer\Json;
use RuntimeException;
use Exception;

class TranslateParsedContent
{
    const WIDGET_PATTERN = '/\{\{widget\s[^}]*\}\}/';
    const TRANSLATABLE_WIDGET_PARAMS = ['anchor_text', 'title', 'description'];
    const TRANSLATABLE_REPEATABLE_PARAMS = ['title', 'content', 'button', 'image_alt'];
    const JSON_ENCODE_FLAGS = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    /**
     * @param Service $serviceHelper
     * @param Translator $translator
     * @param Json $json
     */
    public function __construct(
        protected Service $serviceHelper,
        protected Translator $translator,
        protected Json $json,
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
        $parsedContent,
        string $requestPostValue,
        string $destinationLanguage
    ): mixed {
        if (is_string($parsedContent)) {
            return $this->translateWidgetDirectives($parsedContent, $destinationLanguage);
        }

        $contentSettingsMap = [];
        $result = preg_replace_callback(
            '/content_settings="([^"]*)"/',
            function (array $matches) use (&$contentSettingsMap): string {
                $key = 'CS_PLACEHOLDER_' . count($contentSettingsMap);
                $contentSettingsMap[$key] = $matches[1];
                return 'content_settings="' . $key . '"';
            },
            $requestPostValue
        );

        if ($result === null) {
            throw new RuntimeException(sprintf('preg_replace_callback failed with error %d', preg_last_error()));
        }

        $requestPostValue = html_entity_decode(htmlspecialchars_decode($result));

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

        $result = strpos($requestPostValue, 'data-content-type="html"') !== false
            ? $this->serviceHelper->encodePageBuilderHtmlBox($requestPostValue)
            : $requestPostValue;

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
                'content_settings="' . $this->translateContentSettings($value, $destinationLanguage) . '"',
                $result
            );
        }

        return $result;
    }

    /**
     * @param string $text
     * @param string $destinationLanguage
     * @return string
     * @throws RuntimeException
     * @throws Exception
     */
    protected function translateWidgetDirectives(
        string $text,
        string $destinationLanguage
    ): string {
        [$text, $widgetMap] = $this->extractPlaceholders(
            $text,
            self::WIDGET_PATTERN,
            'WIDGET_PLACEHOLDER_'
        );

        foreach ($widgetMap as $key => $widget) {
            $text = str_replace(
                $key,
                $this->translateWidgetParams($widget, $destinationLanguage),
                $text
            );
        }

        return $text;
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
     * @param string $contentSettings
     * @param string $destinationLanguage
     * @return string
     * @throws Exception
     */
    protected function translateContentSettings(
        string $contentSettings,
        string $destinationLanguage
    ): string {
        $decoded = html_entity_decode($contentSettings, ENT_QUOTES, 'UTF-8');

        try {
            $outer = $this->json->unserialize($decoded);
        } catch (Exception) {
            return $contentSettings;
        }

        if (!is_array($outer) || !isset($outer['data'])) {
            return $contentSettings;
        }

        try {
            $data = $this->json->unserialize($outer['data']);
        } catch (Exception) {
            return $contentSettings;
        }

        if (!is_array($data) || !isset($data['values'])) {
            return $contentSettings;
        }

        $translations = [];

        foreach ($data['values'] as $key => &$value) {
            if (!is_string($value) || trim($value) === '') {
                continue;
            }

            if (str_starts_with($key, 'repeatable_') && str_ends_with($key, '_items')) {
                $original = $value;
                $value = $this->translateRepeatableItems($value, $destinationLanguage);
                $this->collectRepeatableTranslations($original, $value, $translations);
                continue;
            }

            foreach (self::TRANSLATABLE_WIDGET_PARAMS as $suffix) {
                if (str_ends_with($key, '_' . $suffix)) {
                    $original = $value;
                    $value = $this->translator->translate($value, $destinationLanguage);
                    if ($original !== $value) {
                        $translations[$original] = $value;
                    }
                    break;
                }
            }
        }
        unset($value);

        $outer['data'] = json_encode($data, self::JSON_ENCODE_FLAGS);

        if (isset($outer['preview']) && !empty($translations)) {
            foreach ($translations as $original => $translated) {
                $outer['preview'] = str_replace($original, $translated, $outer['preview']);
            }
        }

        return htmlspecialchars(
            json_encode($outer, self::JSON_ENCODE_FLAGS),
            ENT_COMPAT,
            'UTF-8'
        );
    }

    /**
     * @param string $original
     * @param string $translated
     * @param array &$translations
     */
    protected function collectRepeatableTranslations(
        string $original,
        string $translated,
        array &$translations
    ): void {
        foreach (self::TRANSLATABLE_REPEATABLE_PARAMS as $param) {
            preg_match_all('/`' . preg_quote($param, '/') . '`:`([^`]*)`/u', $original, $origMatches);
            preg_match_all('/`' . preg_quote($param, '/') . '`:`([^`]*)`/u', $translated, $transMatches);

            foreach ($origMatches[1] as $j => $origVal) {
                if (isset($transMatches[1][$j]) && $origVal !== $transMatches[1][$j] && trim($origVal) !== '') {
                    $translations[$origVal] = $transMatches[1][$j];
                }
            }
        }
    }

    /**
     * @param string $repeatableString
     * @param string $destinationLanguage
     * @return string
     * @throws RuntimeException
     * @throws Exception
     */
    protected function translateRepeatableItems(
        string $repeatableString,
        string $destinationLanguage
    ): string {
        foreach (self::TRANSLATABLE_REPEATABLE_PARAMS as $param) {
            $result = preg_replace_callback(
                '/(`' . preg_quote($param, '/') . '`:`)([^`]*)(`)/u',
                function (array $matches) use ($destinationLanguage): string {
                    if (trim($matches[2]) === '') {
                        return $matches[0];
                    }
                    return $matches[1]
                        . $this->translator->translate($matches[2], $destinationLanguage)
                        . $matches[3];
                },
                $repeatableString
            );

            if ($result === null) {
                throw new RuntimeException(sprintf('preg_replace_callback failed with error %d', preg_last_error()));
            }

            $repeatableString = $result;
        }

        return $repeatableString;
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

        $result = preg_replace_callback(
            '/(repeatable_[a-z_]+_items=")([^"]*)(")/',
            function (array $matches) use ($destinationLanguage): string {
                if (trim($matches[2]) === '') {
                    return $matches[0];
                }
                return $matches[1]
                    . $this->translateRepeatableItems($matches[2], $destinationLanguage)
                    . $matches[3];
            },
            $widget
        );

        if ($result === null) {
            throw new RuntimeException(sprintf('preg_replace_callback failed with error %d', preg_last_error()));
        }

        return $result;
    }
}
