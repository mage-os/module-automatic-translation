<?php

namespace MageOS\AutomaticTranslation\Model;

use MageOS\AutomaticTranslation\Api\TranslatorInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Model\Translator\TranslatorFactory;

/**
 * Class Translator
 */
class Translator implements TranslatorInterface
{
    protected $translator;
    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;
    /**
     * @var TranslatorFactory
     */
    protected TranslatorFactory $translatorFactory;

    /**
     * Translator constructor.
     * @param ModuleConfig $moduleConfig
     * @param TranslatorFactory $translatorFactory
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        TranslatorFactory $translatorFactory
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->translatorFactory = $translatorFactory;
    }

    /**
     * @return void
     */
    protected function initTranslator()
    {
        $engineClass = $this->moduleConfig->getEngineForTranslation();
        $this->translator = $this->translatorFactory->create($engineClass);
    }

    /**
     * @param string $text
     * @param string $targetLang
     * @param string|null $sourceLang
     * @return string
     * @throw Exception
     */
    public function translate(string $text, string $targetLang, ?string $sourceLang = null): string
    {
        if (empty($this->translator)) {
            $this->initTranslator();
        }

        return $this->translator->translate($text, $targetLang, $sourceLang);
    }
}
