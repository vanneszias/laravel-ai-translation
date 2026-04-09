<?php

declare(strict_types=1);

namespace Statikbe\AiTranslation;

use Illuminate\Support\Manager;
use Statikbe\AiTranslation\Contracts\AiTranslationDriver;
use Statikbe\AiTranslation\Exceptions\TranslationDriverException;
use Statikbe\AiTranslation\Drivers\LaravelAiDriver;
use Statikbe\AiTranslation\Drivers\LibreTranslateDriver;
use Statikbe\AiTranslation\Drivers\NullDriver;

/**
 * Translation Driver Manager.
 *
 * Extends Laravel's Manager to provide driver resolution for translation providers.
 * Use it like: AiTranslation::driver('libretranslate')->translateBatch([...], 'en', 'nl')
 *
 * Custom drivers can be registered via:
 * AiTranslation::extend('my_driver', fn ($app, $config) => new MyDriver($config));
 */
class AiTranslationManager extends Manager
{
    /**
     * Get the default driver name from config.
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('ai-translation.default_driver', 'null');
    }

    /**
     * Create the laravel_ai driver (requires laravel/ai).
     */
    protected function createLaravelAiDriver(): AiTranslationDriver
    {
        $config = $this->config->get('ai-translation.drivers.laravel_ai', []);
        $systemPrompt = $this->resolveSystemPrompt();

        if (!interface_exists(\Laravel\Ai\Contracts\Agent::class)) {
            throw new TranslationDriverException(
                'The laravel_ai translation driver requires the laravel/ai package. '
                . 'Install it with: composer require laravel/ai',
            );
        }

        return new LaravelAiDriver($config, $systemPrompt);
    }

    /**
     * Create the libretranslate driver.
     */
    protected function createLibretranslateDriver(): AiTranslationDriver
    {
        $config = $this->config->get('ai-translation.drivers.libretranslate', []);

        return new LibreTranslateDriver(
            url: rtrim($config['url'] ?? 'https://libretranslate.com', '/'),
            apiKey: $config['api_key'] ?? '',
            timeout: $config['timeout'] ?? 30,
        );
    }

    /**
     * Create the null driver (testing / no-op).
     */
    protected function createNullDriver(): AiTranslationDriver
    {
        return new NullDriver();
    }

    /**
     * Resolve the global system prompt from config, with optional per-group override.
     */
    protected function resolveSystemPrompt(?string $group = null): string
    {
        $globalPrompt = $this->config->get(
            'ai-translation.prompts.system',
            'You are an expert software UI translator. Preserve all placeholders and HTML tags. Return valid JSON.',
        );

        if ($group !== null) {
            $groupOverride = $this->config->get("ai-translation.prompts.group_overrides.{$group}");
            if ($groupOverride) {
                return $globalPrompt . "\n\n" . $groupOverride;
            }
        }

        return $globalPrompt;
    }

    /**
     * Get the system prompt for a specific translation group.
     * This is used by AiTranslationService to build per-group prompts.
     */
    public function getSystemPromptForGroup(string $group): string
    {
        return $this->resolveSystemPrompt($group);
    }

    /**
     * Translate a single string using the default (or specified) driver.
     */
    public function translate(
        string $text,
        string $from,
        string $to,
        array $options = [],
        ?string $driverName = null,
    ): string {
        return $this->driver($driverName)->translate($text, $from, $to, $options);
    }

    /**
     * Translate a batch of strings using the default (or specified) driver.
     *
     * @param  array<string, string>  $texts
     * @return array<string, string>
     */
    public function translateBatch(
        array $texts,
        string $from,
        string $to,
        array $options = [],
        ?string $driverName = null,
    ): array {
        return $this->driver($driverName)->translateBatch($texts, $from, $to, $options);
    }
}
