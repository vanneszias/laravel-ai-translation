<?php

declare(strict_types=1);

namespace Statikbe\AiTranslation\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Statikbe\AiTranslation\AiTranslationService;

/**
 * Queued job for translating a group of missing translation keys.
 *
 * Dispatched by AiTranslationService::translateMissingForGroup() when queue is enabled.
 * Translates all provided key => source text pairs and persists them via the chained translator.
 */
class TranslateGroupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff;

    /**
     * @param  string                 $locale        Target locale.
     * @param  string                 $group         Translation group name.
     * @param  array<string, string>  $missingTexts  Map of key => source text to translate.
     * @param  string|null            $driver        Optional driver name override.
     */
    public function __construct(
        public readonly string $locale,
        public readonly string $group,
        public readonly array $missingTexts,
        public readonly ?string $driver = null,
    ) {
        $this->tries = config('ai-translation.queue.tries', 3);
        $this->backoff = config('ai-translation.queue.retry_after', 60);
    }

    /**
     * Execute the job.
     */
    public function handle(AiTranslationService $service): void
    {
        $service->translateGroupSync(
            locale: $this->locale,
            group: $this->group,
            missingTexts: $this->missingTexts,
            driver: $this->driver,
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $e): void
    {
        Log::error('TranslateGroupJob failed', [
            'locale' => $this->locale,
            'group' => $this->group,
            'driver' => $this->driver,
            'error' => $e->getMessage(),
        ]);
    }
}
