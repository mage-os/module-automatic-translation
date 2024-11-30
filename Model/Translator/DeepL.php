<?php

namespace MageOS\AutomaticTranslation\Model\Translator;

use MageOS\AutomaticTranslation\Api\TranslatorInterface;
use DeepL\Translator as DeepLTranslator;
use DeepL\TranslatorFactory as DeepLTranslatorFactory;
use MageOS\AutomaticTranslation\Helper\ModuleConfig;
use DeepL\TextResult;
use DeepL\DeepLException;

/**
 * Class DeepL
 */
class DeepL implements TranslatorInterface
{
    /**
     * @var DeepLTranslator|null
     */
    protected ?DeepLTranslator $translator = null;
    /**
     * @var DeepLTranslatorFactory
     */
    protected DeepLTranslatorFactory $deepLTranslator;
    /**
     * @var ModuleConfig
     */
    protected ModuleConfig $moduleConfig;

    /**
     * DeepL constructor.
     * @param DeepLTranslatorFactory $deepLTranslator
     * @param ModuleConfig $moduleConfig
     */
    public function __construct(
        DeepLTranslatorFactory $deepLTranslator,
        ModuleConfig $moduleConfig
    ) {
        $this->deepLTranslator = $deepLTranslator;
        $this->moduleConfig = $moduleConfig;
    }

    /**
     * @return void
     */
    protected function initTranslator()
    {
        $this->translator = $this->deepLTranslator->create(['authKey' => $this->moduleConfig->getDeepLAuthKey()]);
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
        if (empty($this->translator)) {
            $this->initTranslator();
        }

        if (substr($targetLang, 2, 1) === '_') {
            $targetLang = str_replace('_', '-', $targetLang);
        }

        if ($sourceLang) {
            $sourceLang = substr($sourceLang, 0, 2);
        }

        try {
            $options = [];
            if (strip_tags($text) !== $text) {
                $options["tag_handling"] = "html";
            }
            /** @var $text TextResult; */
            $result = $this->translator->translateText($text, $sourceLang, $targetLang, $options);

            return $result->text;
        } catch (DeepLException $e) {
            if (strlen($targetLang) > 2) {
                $targetLang = substr($targetLang, 0, 2);

                /** @var $result TextResult; */
                return $this->translate($text, $targetLang, $sourceLang);
            } else {
                throw new DeepLException($e);
            }
        }
    }
}
