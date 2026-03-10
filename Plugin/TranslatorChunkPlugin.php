<?php
declare(strict_types=1);

namespace MageOS\AutomaticTranslation\Plugin;

use MageOS\AutomaticTranslation\Model\Translator;
use RuntimeException;

class TranslatorChunkPlugin
{
    const int MAX_CHUNK_SIZE = 4500;

    const string BLOCK_TAG_PATTERN = '#(</(?:p|div|h[1-6]|ul|ol|li|table|tr|blockquote|section|article|header|footer|figure|figcaption)>)#i';

    const array PLAIN_TEXT_PATTERNS = [
        '/(\n\n)/',
        '/(\n)/',
        '/((?<=[.!?])\s+)/',
        '/(\s+)/',
    ];

    /**
     * @param Translator $subject
     * @param callable $proceed
     * @param string $text
     * @param string $targetLang
     * @param string|null $sourceLang
     * @return string
     * @throws RuntimeException
     */
    public function aroundTranslate(
        Translator $subject,
        callable $proceed,
        string $text,
        string $targetLang,
        ?string $sourceLang = null
    ): string {
        if (mb_strlen($text) <= self::MAX_CHUNK_SIZE) {
            return $proceed($text, $targetLang, $sourceLang);
        }

        $segments = strip_tags($text) !== $text
            ? $this->splitHtml($text)
            : $this->splitByPatterns($text);

        $translated = array_map(
            fn(string $chunk): string => $proceed($chunk, $targetLang, $sourceLang),
            $this->groupIntoChunks($segments)
        );

        return implode('', $translated);
    }

    /**
     * @param string $text
     * @return string[]
     * @throws RuntimeException
     */
    protected function splitHtml(string $text): array
    {
        $parts = preg_split(self::BLOCK_TAG_PATTERN, $text, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($parts === false) {
            throw new RuntimeException(sprintf('preg_split failed with error %d', preg_last_error()));
        }

        if (count($parts) <= 1) {
            return [$text];
        }

        $segments = [];
        $current = '';

        foreach ($parts as $part) {
            $current .= $part;

            if (preg_match(self::BLOCK_TAG_PATTERN, $part) === 1) {
                $segments[] = $current;
                $current = '';
            }
        }

        if ($current !== '') {
            $segments[] = $current;
        }

        return $segments;
    }

    /**
     * @param string $text
     * @param int $fromIndex
     * @return string[]
     * @throws RuntimeException
     */
    protected function splitByPatterns(string $text, int $fromIndex = 0): array
    {
        for ($i = $fromIndex, $count = count(self::PLAIN_TEXT_PATTERNS); $i < $count; $i++) {
            $parts = preg_split(self::PLAIN_TEXT_PATTERNS[$i], $text, -1, PREG_SPLIT_DELIM_CAPTURE);

            if ($parts === false) {
                throw new RuntimeException(sprintf('preg_split failed with error %d', preg_last_error()));
            }

            if (count($parts) <= 1) {
                continue;
            }

            $segments = [];

            for ($j = 0, $partCount = count($parts); $j < $partCount; $j += 2) {
                $segment = $parts[$j] . ($parts[$j + 1] ?? '');

                if ($segment !== '') {
                    $segments[] = $segment;
                }
            }

            return $segments;
        }

        return [$text];
    }

    /**
     * @param string[] $segments
     * @param int $patternIndex
     * @return string[]
     * @throws RuntimeException
     */
    protected function groupIntoChunks(array $segments, int $patternIndex = 0): array
    {
        $chunks = [];
        $current = '';

        foreach ($segments as $segment) {
            if ($current !== '' && mb_strlen($current . $segment) > self::MAX_CHUNK_SIZE) {
                $chunks[] = $current;
                $current = '';
            }

            if ($current === '' && mb_strlen($segment) > self::MAX_CHUNK_SIZE) {
                $subSegments = $this->splitByPatterns($segment, $patternIndex);

                if (count($subSegments) > 1) {
                    array_push($chunks, ...$this->groupIntoChunks($subSegments, $patternIndex + 1));
                    continue;
                }

                $chunks[] = $segment;
                continue;
            }

            $current .= $segment;
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }
}
