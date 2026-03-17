<?php

namespace Statikbe\AiTranslation\Contracts;

interface TranslationDriver
{
    /**
     * Translate a single string.
     *
     * @param  string  $text      The source text to translate.
     * @param  string  $from      The source locale (e.g. 'en').
     * @param  string  $to        The target locale (e.g. 'nl').
     * @param  array   $options   Driver-specific options.
     * @return string             The translated text.
     */
    public function translate(string $text, string $from, string $to, array $options = []): string;

    /**
     * Translate a batch of strings in a single call.
     *
     * The keys of the input array are preserved in the output array.
     * This allows callers to identify which translation corresponds to which key.
     *
     * @param  array<string, string>  $texts    Map of key => source text.
     * @param  string                 $from     The source locale.
     * @param  string                 $to       The target locale.
     * @param  array                  $options  Driver-specific options (e.g. system prompt override).
     * @return array<string, string>            Map of key => translated text.
     */
    public function translateBatch(array $texts, string $from, string $to, array $options = []): array;

    /**
     * Return the driver's identifier name.
     */
    public function getName(): string;
}
