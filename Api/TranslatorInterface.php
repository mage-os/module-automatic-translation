<?php

namespace MageOS\AutomaticTranslation\Api;

use Exception;

/**
 * Interface TranslatorInterface
 */
interface TranslatorInterface
{
    /**
     * @param string $text
     * @param string $targetLang
     * @param string|null $sourceLang
     * @return string
     * @throws Exception
     */
    public function translate(
        string $text,
        string $targetLang,
        ?string $sourceLang = null
    ): string;
}
