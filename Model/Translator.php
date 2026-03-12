<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Model;

use MageOS\AutomaticTranslation\Api\TranslatorInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use MageOS\AutomaticTranslation\Model\Translator\TranslatorFactory;
use Exception;

class Translator implements TranslatorInterface
{
    protected ?TranslatorInterface $translator = null;

    /**
     * @param ModuleConfig $moduleConfig
     * @param TranslatorFactory $translatorFactory
     */
    public function __construct(
        protected ModuleConfig $moduleConfig,
        protected TranslatorFactory $translatorFactory
    ) {
    }

    /**
     * @return void
     */
    protected function initTranslator(): void
    {
        $engineClass = $this->moduleConfig->getEngineForTranslation();
        $this->translator = $this->translatorFactory->create($engineClass);
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
        if (empty($text)) {
            return '';
        }

        if ($this->translator === null) {
            $this->initTranslator();
        }

        return $this->translator->translate($text, $targetLang, $sourceLang);
    }
}
