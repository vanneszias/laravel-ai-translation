<?php

declare(strict_types=1);

namespace Statikbe\AiTranslation\Console\Commands;

use Illuminate\Console\Command;
use Statikbe\AiTranslation\AiTranslationService;

/**
 * Artisan command to translate missing translation keys using an AI provider.
 *
 * Usage:
 *   php artisan ai-translation:translate nl
 *   php artisan ai-translation:translate nl --group=auth --group=validation
 *   php artisan ai-translation:translate nl --driver=libretranslate
 *   php artisan ai-translation:translate nl --sync
 *   php artisan ai-translation:translate nl --dry-run
 */
class TranslateMissingCommand extends Command
{
    protected $signature = 'ai-translation:translate
        {locale : The target locale to translate into (e.g. nl, fr, de)}
        {--group=* : Limit translation to specific groups (can be repeated)}
        {--driver= : Override the default translation driver}
        {--sync : Run synchronously instead of queueing jobs}
        {--dry-run : Show what would be translated without actually translating}
        {--source= : Override the source locale (default: ai-translation.source_locale)}';

    protected $description = 'Translate missing translation keys into the specified locale using AI';

    public function handle(AiTranslationService $service): int
    {
        $locale = $this->argument('locale');
        $groups = $this->option('group') ?? [];
        $driver = $this->option('driver');
        $sync = $this->option('sync') || !config('ai-translation.queue.enabled', true);
        $dryRun = $this->option('dry-run');
        $sourceLocale = $this->option('source') ?? config('ai-translation.source_locale');

        if ($dryRun) {
            $this->warn('[DRY RUN] No translations will be saved.');
        }

        if (!$service->chainedTranslatorIsAvailable()) {
            $this->error('This command requires statikbe/laravel-chained-translator.');
            $this->line('Install it with: composer require statikbe/laravel-chained-translator');

            return self::FAILURE;
        }

        $manager = app(\Statikbe\LaravelChainedTranslator\ChainedTranslationManager::class);
        $allGroups = $manager->getTranslationGroups();

        if ($groups !== []) {
            $allGroups = array_values(array_intersect($allGroups, $groups));
        }

        if ($allGroups === []) {
            $this->warn('No translation groups found.');

            return self::SUCCESS;
        }

        $this->info("Translating missing keys for locale: <comment>{$locale}</comment>");
        $this->info("Source locale: <comment>{$sourceLocale}</comment>");
        $this->info(
            'Driver: <comment>' . ($driver ?? config('ai-translation.default_driver', 'laravel_ai')) . '</comment>',
        );
        $this->info('Mode: <comment>' . ($sync ? 'synchronous' : 'queued') . '</comment>');
        $this->newLine();

        $totalMissing = 0;
        $totalTranslated = 0;

        foreach ($allGroups as $group) {
            $missing = $service->getMissingTranslations($locale, $group);

            if ($missing === []) {
                $this->line("  <info>✓</info> {$group} — no missing keys");
                continue;
            }

            $count = count($missing);
            $totalMissing += $count;

            if ($dryRun) {
                $this->line("  <comment>~</comment> {$group} — {$count} missing key(s) would be translated:");
                foreach ($missing as $key => $text) {
                    $this->line("      {$key}: <fg=gray>{$text}</>");
                }
                continue;
            }

            $this->line("  <comment>→</comment> {$group} — translating {$count} key(s)...");

            if (!$sync) {
                $service->queueMissingForGroup(locale: $locale, group: $group, driver: $driver);
                $this->line("    <info>✓</info> Queued {$count} key(s) for group '{$group}'");
                $totalTranslated += $count;
                continue;
            }

            $result = $service->translateMissingForGroup(locale: $locale, group: $group, driver: $driver);
            $translated = count(array_filter($result));
            $totalTranslated += $translated;
            $this->line("    <info>✓</info> Translated {$translated}/{$count} keys");
        }

        $this->newLine();

        if ($dryRun) {
            $this->info(
                "DRY RUN complete: {$totalMissing} key(s) across "
                . count($allGroups)
                . ' group(s) would be translated.',
            );

            return self::SUCCESS;
        }

        if ($sync) {
            $this->info("Done: {$totalTranslated}/{$totalMissing} key(s) translated.");

            return self::SUCCESS;
        }

        $this->info("Done: {$totalTranslated} key(s) queued for translation.");

        return self::SUCCESS;
    }
}
