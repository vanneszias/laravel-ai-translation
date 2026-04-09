<?php

declare(strict_types=1);

namespace Statikbe\AiTranslation\Drivers;

use Illuminate\Support\Str;
use Laravel\Ai\Enums\Lab;
use Statikbe\AiTranslation\Agents\TranslationAgent;
use Statikbe\AiTranslation\Contracts\AiTranslationDriver;
use Statikbe\AiTranslation\Exceptions\TranslationDriverException;

/**
 * Laravel AI SDK driver.
 *
 * Uses the official laravel/ai package to translate strings via LLMs.
 * Supports all providers available in laravel/ai: OpenAI, Anthropic,
 * Gemini, Ollama, Groq, Mistral, DeepSeek, xAI, Azure.
 *
 * For batch translation, sends all keys in one structured-output LLM call
 * and parses the response back into a key => translation map.
 *
 * @requires laravel/ai ^0.3
 */
class LaravelAiDriver implements AiTranslationDriver
{
    protected Lab|string $provider;

    public function __construct(
        protected array $config,
        protected string $systemPrompt,
    ) {
        $this->provider = $this->resolveProvider($config['provider'] ?? 'openai');
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

        $systemPrompt = $options['system_prompt'] ?? $this->systemPrompt;
        $keys = array_keys($texts);

        $agent = new TranslationAgent(systemPrompt: $systemPrompt, keys: $keys, from: $from, to: $to);

        $inputJson = json_encode($texts, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $prompt = "Translate the following strings:\n\n{$inputJson}";

        try {
            $response = $agent->prompt(
                prompt: $prompt,
                provider: $this->provider,
                model: $this->config['model'] ?? null,
                timeout: $this->config['timeout'] ?? 120,
            );

            // The response is a StructuredAgentResponse — access like an array
            $result = [];
            foreach ($keys as $key) {
                $result[$key] = $response[$key] ?? '';
            }

            return $result;
        } catch (\Throwable $e) {
            throw new TranslationDriverException(
                "LaravelAiDriver failed during batch translation: {$e->getMessage()}",
                previous: $e,
            );
        }
    }

    public static function getName(): string
    {
        return 'laravel_ai';
    }

    protected function resolveProvider(string $provider): Lab|string
    {
        // Map common provider string names to the Laravel\Ai\Enums\Lab enum
        $map = [
            'openai' => Lab::OpenAI,
            'anthropic' => Lab::Anthropic,
            'gemini' => Lab::Gemini,
            'groq' => Lab::Groq,
            'mistral' => Lab::Mistral,
            'ollama' => Lab::Ollama,
            'deepseek' => Lab::DeepSeek,
            'xai' => Lab::xAI,
            'azure' => Lab::Azure,
        ];

        return $map[strtolower($provider)] ?? $provider;
    }
}
