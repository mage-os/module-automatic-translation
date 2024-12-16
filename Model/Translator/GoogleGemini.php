<?php

namespace MageOS\AutomaticTranslation\Model\Translator;

use MageOS\AutomaticTranslation\Api\TranslatorInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use Gemini as GeminiTranslator;
use Gemini\Client as GeminiClient;

/**
 * Class GoogleGemini
 */
class GoogleGemini implements TranslatorInterface
{
    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;
    /**
     * @var GeminiTranslator
     */
    protected GeminiTranslator $geminiTranslator;
    /**
     * @var GeminiClient|null
     */
    protected ?GeminiClient $translator = null;

    /**
     * GoogleGemini constructor.
     * @param ModuleConfig $moduleConfig
     * @param GeminiTranslator $geminiTranslator
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        GeminiTranslator $geminiTranslator
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->geminiTranslator = $geminiTranslator;
    }

    /**
     * @return void
     */
    public function initTranslator()
    {
        $apiKey = $this->moduleConfig->getGeminiApiKey();
        $this->translator = $this->geminiTranslator::client($apiKey);
    }

    /**
     * @param string $text
     * @param string $targetLang
     * @param string|null $sourceLang
     * @return string
     */
    public function translate(string $text, string $targetLang, ?string $sourceLang = null): string
    {
        if (empty($this->translator)) {
            $this->initTranslator();
        }

        $prompt = 'Translate this text';
        $prompt .= (!empty($sourceLang)) ? ' from ' . $sourceLang : '';
        $prompt .= ' to ' . $targetLang;
        $prompt .= ' writing the result directly without premise or conclusion or consideration, keeping the html code unchanged, if i don\'t have initial html don\'t add it';
        $prompt .= ': ' . $text;

        $response = $this->translator
            ->generativeModel($this->moduleConfig->getGeminiModel())
            ->generateContent($prompt)
            ->toArray();

        $return = '';
        foreach ($response['candidates'][0]['content']['parts'] as $part) {
            $return .= $part['text'];
        }

        return trim($return);
    }
}
