<?php

declare(strict_types=1);

namespace Statikbe\AiTranslation\Drivers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use SensitiveParameter;
use Statikbe\AiTranslation\Contracts\AiTranslationDriver;
use Statikbe\AiTranslation\Exceptions\TranslationDriverException;

/**
 * LibreTranslate driver.
 *
 * Uses the LibreTranslate open-source machine translation REST API.
 * Can be self-hosted (free) or use the managed service at libretranslate.com.
 *
 * @see https://docs.libretranslate.com
 */
class LibreTranslateDriver implements AiTranslationDriver
{
    public function __construct(
        protected string $url,
        #[SensitiveParameter]
        protected string $apiKey = '',
        protected int $timeout = 30,
    ) {}

    public function translate(string $text, string $from, string $to, array $options = []): string
    {
        try {
            $response = Http::timeout($this->timeout)->post(
                $this->url . '/translate',
                $this->buildPayload($text, $from, $to),
            );
        } catch (ConnectionException $e) {
            throw new TranslationDriverException("LibreTranslate request failed: {$e->getMessage()}", previous: $e);
        }

        if ($response->failed()) {
            throw new TranslationDriverException(
                "LibreTranslate API returned HTTP {$response->status()}: {$response->body()}",
            );
        }

        $translated = $response->json('translatedText', '');

        return is_string($translated) ? $translated : '';
    }

    public function translateBatch(array $texts, string $from, string $to, array $options = []): array
    {
        // LibreTranslate does not have a native batch endpoint,
        // so we translate each string individually.
        // Future: could use concurrent requests via Http::pool().
        $results = [];

        foreach ($texts as $key => $text) {
            $results[$key] = $this->translate($text, $from, $to, $options);
        }

        return $results;
    }

    public static function getName(): string
    {
        return 'libretranslate';
    }

    protected function buildPayload(string $text, string $from, string $to): array
    {
        $payload = [
            'q' => $text,
            'source' => $from,
            'target' => $to,
            'format' => 'text',
        ];

        if ($this->apiKey !== '') {
            $payload['api_key'] = $this->apiKey;
        }

        return $payload;
    }
}
