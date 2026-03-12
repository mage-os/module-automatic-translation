<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Model\Translator;

use Gemini as GeminiTranslator;
use Gemini\Client as GeminiClient;
use MageOS\AutomaticTranslation\Api\TranslatorInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use Exception;

class GoogleGemini implements TranslatorInterface
{
    protected ?GeminiClient $translator = null;

    /**
     * @param ModuleConfig $moduleConfig
     * @param GeminiTranslator $geminiTranslator
     */
    public function __construct(
        protected ModuleConfig $moduleConfig,
        protected GeminiTranslator $geminiTranslator
    ) {
    }

    /**
     * @param string $text
     * @param string $targetLang
     * @param string|null $sourceLang
     * @return string
     * @throws Exception
     */
    public function translate(string $text, string $targetLang, ?string $sourceLang = null): string
    {
        $this->translator ??= $this->geminiTranslator::client($this->moduleConfig->getGeminiApiKey());

        $sourceFragment = $sourceLang ? ' from ' . $sourceLang : '';
        $prompt = 'Translate this text' . $sourceFragment . ' to ' . $targetLang
            . ' writing the result directly without premise or conclusion or consideration, keeping the html code'
            . ' unchanged, if i don\'t have initial html don\'t add it: ' . $text;

        $response = $this->translator
            ->generativeModel($this->moduleConfig->getGeminiModel())
            ->generateContent($prompt)
            ->toArray();

        return trim(implode('', array_column($response['candidates'][0]['content']['parts'], 'text')));
    }
}
