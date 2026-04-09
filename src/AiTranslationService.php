<?php

declare(strict_types=1);

namespace Statikbe\AiTranslation;

use RuntimeException;
use Statikbe\AiTranslation\Jobs\TranslateGroupJob;

/**
 * High-level translation service.
 *
 * Bridges the translation driver system with the laravel-chained-translator
 * (if available) to discover missing translations and persist results.
 *
 * Usage (without chained translator):
 *   app(AiTranslationService::class)->translate('Hello world', 'en', 'nl')
 *
 * Usage (with chained translator):
 *   app(AiTranslationService::class)->translateMissingForLocale('nl')
 */
class AiTranslationService
{
    public function __construct(
        protected AiTranslationManager $manager,
    ) {}

    /**
     * Translate a single string synchronously.
     */
    public function translate(
        string $text,
        string $from,
        string $to,
        array $options = [],
        ?string $driver = null,
    ): string {
        return $this->manager->driver($driver)->translate($text, $from, $to, $options);
    }

    /**
     * Translate a batch of strings synchronously.
     *
     * @param  array<string, string>  $texts
     * @return array<string, string>
     */
    public function translateBatch(
        array $texts,
        string $from,
        string $to,
        array $options = [],
        ?string $driver = null,
    ): array {
        return $this->manager->driver($driver)->translateBatch($texts, $from, $to, $options);
    }

    /**
     * Translate a single translation key and optionally persist it via the chained translator.
     *
     * @param  string       $locale      Target locale.
     * @param  string       $group       Translation group (e.g. 'auth', 'validation').
     * @param  string       $key         The dot-notated translation key.
     * @param  string       $sourceText  The source text to translate.
     * @param  string|null  $driver      Optional driver override.
     * @return string                    The translated text.
     */
    public function translateKey(
        string $locale,
        string $group,
        string $key,
        string $sourceText,
        ?string $driver = null,
    ): string {
        $sourceLocale = config('ai-translation.source_locale');
        $systemPrompt = $this->manager->getSystemPromptForGroup($group);

        $translated = $this->manager->driver($driver)->translate(
            text: $sourceText,
            from: $sourceLocale,
            to: $locale,
            options: ['system_prompt' => $systemPrompt],
        );

        if ($translated !== '' && $this->chainedTranslatorIsAvailable()) {
            $this->saveViaChainedTranslator($locale, $group, $key, $translated);
        }

        return $translated;
    }

    /**
     * Translate all missing keys for a given locale and group, dispatching a queued job.
     *
     * Requires statikbe/laravel-chained-translator to be installed.
     *
     * @param  string       $locale  Target locale.
     * @param  string       $group   Translation group.
     * @param  string|null  $driver  Optional driver override.
     */
    public function queueMissingForGroup(string $locale, string $group, ?string $driver = null): void
    {
        $missingTexts = $this->getMissingTranslations($locale, $group);

        if ($missingTexts === []) {
            return;
        }

        TranslateGroupJob::dispatch($locale, $group, $missingTexts, $driver)
            ->onConnection(config('ai-translation.queue.connection'))
            ->onQueue(config('ai-translation.queue.queue_name', 'translations'));
    }

    /**
     * Translate all missing keys for a given locale and group synchronously.
     *
     * Requires statikbe/laravel-chained-translator to be installed.
     *
     * @param  string       $locale  Target locale.
     * @param  string       $group   Translation group.
     * @param  string|null  $driver  Optional driver override.
     * @return array<string, string> The translated key => value map.
     */
    public function translateMissingForGroup(string $locale, string $group, ?string $driver = null): array
    {
        $missingTexts = $this->getMissingTranslations($locale, $group);

        if ($missingTexts === []) {
            return [];
        }

        return $this->translateGroupSync($locale, $group, $missingTexts, $driver);
    }

    /**
     * Queue all missing translation keys for a given locale across all groups.
     *
     * Requires statikbe/laravel-chained-translator to be installed.
     *
     * @param  string       $locale   Target locale.
     * @param  array        $groups   Limit to specific groups (empty = all groups).
     * @param  string|null  $driver   Optional driver override.
     */
    public function queueMissingForLocale(string $locale, array $groups = [], ?string $driver = null): void
    {
        foreach ($this->resolveGroups($locale, $groups) as $group) {
            $this->queueMissingForGroup($locale, $group, $driver);
        }
    }

    /**
     * Synchronously translate all missing keys for a given locale across all groups.
     *
     * Requires statikbe/laravel-chained-translator to be installed.
     *
     * @param  string       $locale   Target locale.
     * @param  array        $groups   Limit to specific groups (empty = all groups).
     * @param  string|null  $driver   Optional driver override.
     */
    public function translateMissingForLocale(string $locale, array $groups = [], ?string $driver = null): void
    {
        foreach ($this->resolveGroups($locale, $groups) as $group) {
            $this->translateMissingForGroup($locale, $group, $driver);
        }
    }

    /**
     * Resolve and filter the translation groups for a locale operation.
     *
     * @param  array  $filterGroups  Limit to specific groups (empty = all groups).
     * @return array<string>
     */
    protected function resolveGroups(string $locale, array $filterGroups = []): array
    {
        if (!$this->chainedTranslatorIsAvailable()) {
            throw new RuntimeException(
                'This operation requires statikbe/laravel-chained-translator. '
                . 'Install it with: composer require statikbe/laravel-chained-translator',
            );
        }

        $allGroups = $this->getChainedTranslationManager()->getTranslationGroups();

        if ($filterGroups === []) {
            return $allGroups;
        }

        return array_values(array_intersect($allGroups, $filterGroups));
    }

    /**
     * Perform a synchronous group translation and persist results.
     *
     * This is called directly or from within TranslateGroupJob.
     *
     * @param  string                 $locale
     * @param  string                 $group
     * @param  array<string, string>  $missingTexts  Key => source text map.
     * @param  string|null            $driver
     * @return array<string, string>
     */
    public function translateGroupSync(
        string $locale,
        string $group,
        array $missingTexts,
        ?string $driver = null,
    ): array {
        $sourceLocale = config('ai-translation.source_locale');
        $systemPrompt = $this->manager->getSystemPromptForGroup($group);

        $translated = $this->manager->driver($driver)->translateBatch(
            texts: $missingTexts,
            from: $sourceLocale,
            to: $locale,
            options: ['system_prompt' => $systemPrompt],
        );

        if ($this->chainedTranslatorIsAvailable()) {
            foreach ($translated as $key => $value) {
                if ($value === '') {
                    continue;
                }

                $this->saveViaChainedTranslator($locale, $group, $key, $value);
            }
        }

        return $translated;
    }

    /**
     * Get the keys that are missing translations for a given locale + group.
     * Returns a map of dotted-key => source text for translation.
     *
     * @return array<string, string>
     */
    public function getMissingTranslations(string $locale, string $group): array
    {
        if (!$this->chainedTranslatorIsAvailable()) {
            return [];
        }

        $manager = $this->getChainedTranslationManager();
        $sourceLocale = config('ai-translation.source_locale');

        $sourceTranslations = $manager->getTranslationsForGroup($sourceLocale, $group);
        $existingTranslations = $manager->getTranslationsForGroup($locale, $group);

        $missing = [];
        foreach ($sourceTranslations as $key => $value) {
            $existing = $existingTranslations[$key] ?? null;

            if ($existing !== null && $existing !== '') {
                continue;
            }

            $missing[$key] = $value;
        }

        return $missing;
    }

    /**
     * Check if the laravel-chained-translator is available.
     */
    public function chainedTranslatorIsAvailable(): bool
    {
        return class_exists(\Statikbe\LaravelChainedTranslator\ChainedTranslationManager::class);
    }

    /**
     * Get the ChainedTranslationManager instance from the container.
     */
    protected function getChainedTranslationManager(): \Statikbe\LaravelChainedTranslator\ChainedTranslationManager
    {
        return app(\Statikbe\LaravelChainedTranslator\ChainedTranslationManager::class);
    }

    /**
     * Persist a translation via the chained translator's save() method.
     */
    protected function saveViaChainedTranslator(string $locale, string $group, string $key, string $value): void
    {
        $this->getChainedTranslationManager()->save($locale, $group, $key, $value);
    }
}
