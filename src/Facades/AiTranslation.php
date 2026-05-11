<?php

declare(strict_types=1);

namespace Statikbe\AiTranslation\Facades;

use Illuminate\Support\Facades\Facade;
use Statikbe\AiTranslation\AiTranslationManager;

/**
 * @method static string translate(string $text, string $from, string $to, array $options = [], ?string $driverName = null)
 * @method static array  translateBatch(array $texts, string $from, string $to, array $options = [], ?string $driverName = null)
 * @method static \Statikbe\AiTranslation\Contracts\AiTranslationDriver driver(?string $driver = null)
 * @method static string getSystemPromptForGroup(string $group)
 *
 * @see \Statikbe\AiTranslation\AiTranslationManager
 */
class AiTranslation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AiTranslationManager::class;
    }
}
