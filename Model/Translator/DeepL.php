<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Model\Translator;

use DeepL\Translator as DeepLTranslator;
use DeepL\TranslatorFactory as DeepLTranslatorFactory;
use MageOS\AutomaticTranslation\Api\TranslatorInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use DeepL\DeepLException;

class DeepL implements TranslatorInterface
{
    const array REGIONAL_VARIANTS_LANGUAGES = [
        'en',
        'pt',
        'es',
        'zh'
    ];

    protected ?DeepLTranslator $translator = null;

    /**
     * @param DeepLTranslatorFactory $deepLTranslator
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        protected DeepLTranslatorFactory $deepLTranslator,
        protected ModuleConfig $moduleConfig
    ) {
    }

    /**
     * @param string $text
     * @param string $targetLang
     * @param string|null $sourceLang
     * @return string
     * @throws DeepLException
     */
    public function translate(string $text, string $targetLang, ?string $sourceLang = null): string
    {
        $this->translator ??= $this->deepLTranslator->create(
            ['authKey' => $this->moduleConfig->getDeepLAuthKey()]
        );

        $targetLang = $this->normalizeTargetLang($targetLang);
        $sourceLang = $sourceLang ? substr($sourceLang, 0, 2) : null;

        $options = strip_tags($text) !== $text ? ['tag_handling' => 'html'] : [];

        try {
            return $this->translator->translateText($text, $sourceLang, $targetLang, $options)->text;
        } catch (DeepLException $e) {
            if (str_contains($e->getMessage(), 'is deprecated') && strlen($targetLang) > 2) {
                return $this->translate($text, substr($targetLang, 0, 2), $sourceLang);
            }
            throw new DeepLException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string $lang
     * @return string
     */
    protected function normalizeTargetLang(string $lang): string
    {
        if (($lang[2] ?? '') !== '_') {
            return $lang;
        }

        $lang = str_replace('_', '-', $lang);

        return in_array(substr($lang, 0, 2), self::REGIONAL_VARIANTS_LANGUAGES, true)
            ? $lang
            : substr($lang, 0, 2);
    }
}
