<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Translation Driver
    |--------------------------------------------------------------------------
    |
    | The driver to use when no driver is explicitly specified.
    | Available drivers: "laravel_ai", "libretranslate", "null"
    |
    */
    'default_driver' => env('AI_TRANSLATION_DRIVER', 'laravel_ai'),

    /*
    |--------------------------------------------------------------------------
    | Source Locale
    |--------------------------------------------------------------------------
    |
    | The locale of the source strings (the strings you want to translate from).
    | Defaults to the application locale.
    |
    */
    'source_locale' => env('AI_TRANSLATION_SOURCE_LOCALE', env('APP_LOCALE', 'en')),

    /*
    |--------------------------------------------------------------------------
    | Translation Drivers
    |--------------------------------------------------------------------------
    |
    | Configure each translation driver here. You can define multiple
    | configurations for the same driver type (e.g., two different LLM models).
    |
    */
    'drivers' => [

        /*
         * Laravel AI SDK driver (laravel/ai).
         * Uses LLMs (OpenAI, Anthropic, Gemini, Ollama, etc.) for high-quality
         * context-aware translation. Batches an entire translation group per call.
         *
         * Requires: composer require laravel/ai
         */
        'laravel_ai' => [
            'driver' => 'laravel_ai',

            // The AI provider to use. Corresponds to Laravel\Ai\Enums\Lab values.
            // Options: 'openai', 'anthropic', 'gemini', 'ollama', 'groq', 'mistral',
            //          'deepseek', 'xai', 'azure', 'perplexity'
            'provider' => env('AI_TRANSLATION_PROVIDER', 'openai'),

            // The model to use. Leave null to use the provider's default.
            'model' => env('AI_TRANSLATION_MODEL', null),

            // Max tokens for the response. Adjust based on batch size.
            'max_tokens' => env('AI_TRANSLATION_MAX_TOKENS', 4096),

            // Temperature (0.0 = deterministic, 1.0 = creative). Keep low for translation.
            'temperature' => 0.1,

            // HTTP timeout in seconds for each request.
            'timeout' => 120,
        ],

        /*
         * LibreTranslate driver.
         * Uses the LibreTranslate open-source machine translation API.
         * Can be self-hosted (free) or use the managed service (requires API key).
         *
         * Self-hosted: pip install libretranslate && libretranslate
         * Managed: https://portal.libretranslate.com
         */
        'libretranslate' => [
            'driver' => 'libretranslate',

            // The URL of your LibreTranslate instance.
            'url' => env('LIBRETRANSLATE_URL', 'https://libretranslate.com'),

            // API key (required for the managed service, optional for self-hosted).
            'api_key' => env('LIBRETRANSLATE_API_KEY', ''),

            // HTTP timeout in seconds.
            'timeout' => 30,
        ],

        /*
         * Null driver — returns empty strings. Useful for testing.
         */
        'null' => [
            'driver' => 'null',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Translation Prompts (for LLM-based drivers)
    |--------------------------------------------------------------------------
    |
    | Configure the system prompts used when sending strings to LLM providers.
    | The system prompt shapes translation quality — be specific about your app's
    | domain, tone, and any constraints (e.g., keep placeholders like :name intact).
    |
    */
    'prompts' => [

        /*
         * The global system prompt sent to the LLM before every translation request.
         * Customize this for your application's domain and tone.
         */
        'system' => env(
            'AI_TRANSLATION_SYSTEM_PROMPT',
            'You are an expert translator specializing in software UI translation. ' .
            'Translate the given strings accurately and naturally. ' .
            'Rules: ' .
            '1. Preserve all placeholder variables (e.g., :name, :count, {value}, %s). ' .
            '2. Preserve all HTML tags if present. ' .
            '3. Match the tone and register of the source string (formal/informal). ' .
            '4. Do not add explanations — only return the translated strings. ' .
            '5. Return a valid JSON object with the exact same keys as the input.'
        ),

        /*
         * Per-group system prompt overrides.
         * Use the translation group name as the key to provide a more specific
         * prompt for that group (e.g., legal text, marketing copy, etc.).
         *
         * Example:
         * 'validation' => 'You are translating Laravel form validation error messages. Keep them concise and user-friendly.',
         * 'emails'     => 'You are translating marketing email content. Use a warm, professional tone.',
         */
        'group_overrides' => [
            // 'validation' => 'Keep validation messages concise and clear.',
            // 'auth' => 'Use formal language for authentication messages.',
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Control how translation jobs are dispatched to the queue.
    | When enabled, the Artisan command dispatches jobs for background processing.
    |
    */
    'queue' => [

        // Whether to use the queue by default. Can be overridden per command call.
        'enabled' => env('AI_TRANSLATION_QUEUE', true),

        // The queue connection to use. null = use the default connection.
        'connection' => env('AI_TRANSLATION_QUEUE_CONNECTION', null),

        // The queue name for translation jobs.
        'queue_name' => env('AI_TRANSLATION_QUEUE_NAME', 'translations'),

        // Max number of retries for a failed translation job.
        'tries' => 3,

        // Seconds to wait before retrying a failed job.
        'retry_after' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Batch Size
    |--------------------------------------------------------------------------
    |
    | For LLM drivers, controls how many translation keys are sent in a single
    | API call. Larger batches are more efficient but may exceed token limits.
    | For LibreTranslate, each key is always sent individually.
    |
    */
    'batch_size' => env('AI_TRANSLATION_BATCH_SIZE', 50),

];
