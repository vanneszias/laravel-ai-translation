<?php

namespace Statikbe\AiTranslation\Drivers;

use Statikbe\AiTranslation\Contracts\TranslationDriver;

/**
 * No-op driver — returns empty strings for all translations.
 * Useful for testing or as a placeholder when no provider is configured.
 */
class NullDriver implements TranslationDriver
{
    public function translate(string $text, string $from, string $to, array $options = []): string
    {
        return '';
    }

    public function translateBatch(array $texts, string $from, string $to, array $options = []): array
    {
        return array_map(fn() => '', $texts);
    }

    public function getName(): string
    {
        return 'null';
    }
}
