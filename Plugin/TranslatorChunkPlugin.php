<?php

declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Plugin;

use MageOS\AutomaticTranslation\Model\Translator as Subject;
use MageOS\AutomaticTranslation\Service\TextChunker;
use RuntimeException;

class TranslatorChunkPlugin
{
    /**
     * @param TextChunker $textChunker
     */
    public function __construct(
        protected TextChunker $textChunker
    ) {
    }

    /**
     * @param Subject $subject
     * @param callable $proceed
     * @param string $text
     * @param string $targetLang
     * @param string|null $sourceLang
     * @return string
     * @throws RuntimeException
     */
    public function aroundTranslate(
        Subject $subject,
        callable $proceed,
        string $text,
        string $targetLang,
        ?string $sourceLang = null
    ): string {
        $chunks = $this->textChunker->chunk($text);

        if (count($chunks) === 1) {
            return $proceed($text, $targetLang, $sourceLang);
        }

        return implode('', array_map(
            fn(string $chunk): string => $proceed($chunk, $targetLang, $sourceLang),
            $chunks
        ));
    }
}
