<?php

declare(strict_types=1);

namespace Statikbe\AiTranslation\Drivers;

use Statikbe\AiTranslation\Contracts\AiTranslationDriver;

/**
 * No-op driver — returns empty strings for all translations.
 * Useful for testing or as a placeholder when no provider is configured.
 */
class NullDriver implements AiTranslationDriver
{
    public function translate(string $text, string $from, string $to, array $options = []): string
    {
        return $text;
    }

    public function translateBatch(array $texts, string $from, string $to, array $options = []): array
    {
        return $texts;
    }

    public static function getName(): string
    {
        return 'null';
    }
}
