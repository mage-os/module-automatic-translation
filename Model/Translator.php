<?php

namespace MageOS\AutomaticTranslation\Model;

use MageOS\AutomaticTranslation\Api\TranslatorInterface;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use Magento\Framework\ObjectManagerInterface;

class Translator implements TranslatorInterface
{
    protected $translator;
    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;
    /**
     * @var ObjectManagerInterface
     */
    protected ObjectManagerInterface $objectManager;

    /**
     * @param ModuleConfig $moduleConfig
     * @param ObjectManagerInterface $objectManager
     */
    public function __construct(
        ModuleConfig $moduleConfig,
        ObjectManagerInterface $objectManager
    ) {
        $this->moduleConfig = $moduleConfig;
        $this->objectManager = $objectManager;
    }

    /**
     * @return void
     */
    protected function initTranslator()
    {
        $engineClass = $this->moduleConfig->getEngineForTranslation();
        $this->translator = $this->objectManager->create($engineClass);
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
