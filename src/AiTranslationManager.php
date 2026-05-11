<?php

declare(strict_types=1);

namespace Statikbe\AiTranslation;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Manager;
use Statikbe\AiTranslation\Contracts\AiTranslationDriver;
use Statikbe\AiTranslation\Drivers\LaravelAiDriver;
use Statikbe\AiTranslation\Drivers\LibreTranslateDriver;
use Statikbe\AiTranslation\Drivers\NullDriver;
use Statikbe\AiTranslation\Exceptions\TranslationDriverException;

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
        return Config::string('ai-translation.default_driver', 'null');
    }

    /**
     * Resolve a translation driver instance.
     *
     * @param  string|null  $driver
     */
    public function driver($driver = null): AiTranslationDriver
    {
        $resolved = parent::driver($driver);

        if (!$resolved instanceof AiTranslationDriver) {
            throw new TranslationDriverException(
                'The resolved translation driver must implement ' . AiTranslationDriver::class . '.',
            );
        }

        return $resolved;
    }

    /**
     * Create the laravel_ai driver (requires laravel/ai).
     */
    protected function createLaravelAiDriver(): AiTranslationDriver
    {
        if (!interface_exists(\Laravel\Ai\Contracts\Agent::class)) {
            throw new TranslationDriverException('The laravel_ai translation driver requires the laravel/ai package. '
            . 'Install it with: composer require laravel/ai');
        }

        return new LaravelAiDriver(
            Config::array('ai-translation.drivers.laravel_ai', []),
            $this->resolveSystemPrompt(),
        );
    }

    /**
     * Create the libretranslate driver.
     */
    protected function createLibretranslateDriver(): AiTranslationDriver
    {
        $config = Config::array('ai-translation.drivers.libretranslate', []);

        $url = $config['url'] ?? null;
        $apiKey = $config['api_key'] ?? null;
        $timeout = $config['timeout'] ?? null;

        return new LibreTranslateDriver(
            url: rtrim(is_string($url) ? $url : 'https://libretranslate.com', '/'),
            apiKey: is_string($apiKey) ? $apiKey : '',
            timeout: is_int($timeout) ? $timeout : 30,
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
     *
     * A group override can be a plain string (appended to the global prompt) or an
     * array with 'prompt' and 'replace' => true keys (replaces the global prompt entirely).
     */
    protected function resolveSystemPrompt(?string $group = null): string
    {
        $globalPrompt = Config::get('ai-translation.prompts.system');

        if (!is_string($globalPrompt) || $globalPrompt === '') {
            $globalPrompt = trim(view('ai-translation::prompts.system')->render());
        }

        if ($group === null) {
            return $globalPrompt;
        }

        $groupOverride = Config::get("ai-translation.prompts.group_overrides.{$group}");

        if (is_string($groupOverride) && $groupOverride !== '') {
            return $globalPrompt . "\n\n" . $groupOverride;
        }

        if (is_array($groupOverride)) {
            $overrideText = $groupOverride['prompt'] ?? null;
            $overrideText = is_string($overrideText) ? $overrideText : '';
            $replace = ($groupOverride['replace'] ?? null) === true;

            if ($overrideText !== '') {
                return $replace ? $overrideText : $globalPrompt . "\n\n" . $overrideText;
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
     *
     * @param  array<string, mixed>  $options
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
     * @param  array<string, mixed>  $options
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
