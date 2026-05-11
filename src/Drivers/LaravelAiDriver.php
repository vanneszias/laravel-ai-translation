<?php

declare(strict_types=1);

namespace Statikbe\AiTranslation\Drivers;

use Illuminate\Support\Str;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Statikbe\AiTranslation\Agents\TranslationAgent;
use Statikbe\AiTranslation\Contracts\AiTranslationDriver;
use Statikbe\AiTranslation\Exceptions\TranslationDriverException;

/**
 * Laravel AI SDK driver.
 *
 * Uses the official laravel/ai package to translate strings via LLMs.
 * The provider string is passed directly to laravel/ai (e.g. 'openai',
 * 'anthropic', 'gemini') — no hardcoded mapping is maintained here so
 * the list stays in sync with whatever laravel/ai supports.
 *
 * For batch translation, sends all keys in one structured-output LLM call
 * and parses the response back into a key => translation map.
 *
 * @requires laravel/ai ^0.3
 */
class LaravelAiDriver implements AiTranslationDriver
{
    protected string $provider;

    /**
     * @param  array<array-key, mixed>  $config
     */
    public function __construct(
        protected array $config,
        protected string $systemPrompt,
    ) {
        $provider = $config['provider'] ?? null;
        $this->provider = is_string($provider) && $provider !== '' ? $provider : 'openai';
    }

    public function translate(string $text, string $from, string $to, array $options = []): string
    {
        $key = Str::uuid()->toString();
        $results = $this->translateBatch([$key => $text], $from, $to, $options);

        return $results[$key] ?? '';
    }

    public function translateBatch(array $texts, string $from, string $to, array $options = []): array
    {
        if ($texts === []) {
            return [];
        }

        $systemPromptOption = $options['system_prompt'] ?? null;
        $systemPrompt = is_string($systemPromptOption) ? $systemPromptOption : $this->systemPrompt;

        $keys = array_map(strval(...), array_keys($texts));

        $agent = new TranslationAgent(systemPrompt: $systemPrompt, keys: $keys, from: $from, to: $to);

        $inputJson = json_encode($texts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        if ($inputJson === false) {
            throw new TranslationDriverException('Failed to JSON-encode the strings to translate.');
        }

        $prompt = "Translate the following strings:\n\n{$inputJson}";

        $model = $this->config['model'] ?? null;
        $timeout = $this->config['timeout'] ?? null;

        try {
            $response = $agent->prompt(
                prompt: $prompt,
                provider: $this->provider,
                model: is_string($model) ? $model : null,
                timeout: is_int($timeout) ? $timeout : 120,
            );
        } catch (\Throwable $e) {
            throw new TranslationDriverException(
                "LaravelAiDriver failed during batch translation: {$e->getMessage()}",
                previous: $e,
            );
        }

        if (!$response instanceof StructuredAgentResponse) {
            throw new TranslationDriverException('The AI provider did not return a structured response.');
        }

        $structured = $response->structured;

        $result = [];
        foreach ($keys as $key) {
            $value = $structured[$key] ?? null;
            $result[$key] = is_string($value) ? $value : '';
        }

        return $result;
    }

    public static function getName(): string
    {
        return 'laravel_ai';
    }
}
