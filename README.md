# laravel-ai-translation

A modular AI translation gateway for Laravel. Supports LLM providers via
[laravel/ai](https://github.com/laravel/ai) (OpenAI, Anthropic, Gemini, …) and
[LibreTranslate](https://libretranslate.com). Integrates with
[laravel-chained-translator](https://github.com/statikbe/laravel-chained-translator)
to discover missing keys and persist results.

---

## Installation

```bash
composer require statikbe/laravel-ai-translation
```

Publish the config file:

```bash
php artisan vendor:publish --tag=ai-translation-config
```

Optionally publish the default system prompt Blade view so you can customise it:

```bash
php artisan vendor:publish --tag=ai-translation-views
```

---

## Drivers

### laravel_ai (LLM — recommended)

Requires `laravel/ai`:

```bash
composer require laravel/ai
```

Configure your provider in `config/ai-translation.php` (or via `.env`):

```env
AI_TRANSLATION_DRIVER=laravel_ai
AI_TRANSLATION_PROVIDER=openai   # openai | anthropic | gemini | ollama | …
AI_TRANSLATION_MODEL=gpt-4o
```

Set the provider API key expected by `laravel/ai` (see `.env.example`):

```env
OPENAI_API_KEY=your-key
```

### libretranslate (open-source machine translation)

```env
AI_TRANSLATION_DRIVER=libretranslate
LIBRETRANSLATE_URL=https://libretranslate.com
LIBRETRANSLATE_API_KEY=your-key
```

### null (testing / no-op)

Returns the input string unchanged. Useful in tests or when no provider is configured.

---

## Getting started

1) Install the package and publish the config.

```bash
composer require statikbe/laravel-ai-translation
php artisan vendor:publish --tag=ai-translation-config
```

2) Choose a driver and provider in your `.env`.

```env
AI_TRANSLATION_DRIVER=laravel_ai
AI_TRANSLATION_PROVIDER=openai
```

3) Set the matching API key for your provider.

```env
OPENAI_API_KEY=your-key
```

Alternative provider keys (from `.env.example`):

```env
ANTHROPIC_API_KEY=your-key
GEMINI_API_KEY=your-key
GROQ_API_KEY=your-key
MISTRAL_API_KEY=your-key
DEEPSEEK_API_KEY=your-key
XAI_API_KEY=your-key
AZURE_OPENAI_API_KEY=your-key
OLLAMA_API_KEY=optional
PERPLEXITY_API_KEY=your-key
OPENROUTER_API_KEY=your-key
VOYAGEAI_API_KEY=your-key
```

4) Run a translation.

```bash
php artisan ai-translation:translate nl
```

If you use queues, remember to restart workers after changing env/config.

---

## Artisan command

Requires `statikbe/laravel-chained-translator`:

```bash
composer require statikbe/laravel-chained-translator
```

Translate all missing keys for a locale:

```bash
php artisan ai-translation:translate nl
```

Limit to specific groups:

```bash
php artisan ai-translation:translate nl --group=auth --group=validation
```

Override the driver:

```bash
php artisan ai-translation:translate nl --driver=libretranslate
```

Run synchronously (instead of dispatching queue jobs):

```bash
php artisan ai-translation:translate nl --sync
```

Preview what would be translated without saving anything:

```bash
php artisan ai-translation:translate nl --dry-run
```

Override the source locale:

```bash
php artisan ai-translation:translate nl --source=en
```

---

## Programmatic usage

```php
use Statikbe\AiTranslation\AiTranslationService;

// Single string
app(AiTranslationService::class)->translate('Hello world', 'en', 'nl');

// Batch
app(AiTranslationService::class)->translateBatch(
    ['greeting' => 'Hello', 'farewell' => 'Goodbye'],
    'en', 'nl'
);

// Translate all missing keys for a locale (requires chained-translator)
app(AiTranslationService::class)->translateMissingForLocale('nl');

// Queue missing keys for background processing
app(AiTranslationService::class)->queueMissingForLocale('nl');
```

Via the facade:

```php
use Statikbe\AiTranslation\Facades\AiTranslation;

AiTranslation::translate('Hello', 'en', 'nl');
AiTranslation::driver('libretranslate')->translateBatch(['key' => 'text'], 'en', 'nl');
```

---

## Queue configuration

```env
AI_TRANSLATION_QUEUE=true
AI_TRANSLATION_QUEUE_CONNECTION=redis
AI_TRANSLATION_QUEUE_NAME=translations
```

Run the worker:

```bash
php artisan queue:work --queue=translations
```

---

## Custom system prompts

### Via config

Override globally in `config/ai-translation.php`:

```php
'prompts' => [
    'system' => 'Your custom global prompt here.',
    'group_overrides' => [
        // Append to global prompt:
        'validation' => 'Keep validation messages concise and user-friendly.',
        // Fully replace the global prompt:
        'emails' => [
            'prompt'  => 'You are translating marketing email content. Use a warm, professional tone.',
            'replace' => true,
        ],
    ],
],
```

### Via published Blade view

After publishing with `php artisan vendor:publish --tag=ai-translation-views`, edit
`resources/views/vendor/ai-translation/prompts/system.blade.php`.
The config value takes precedence if set.

---

## Testing

```bash
composer test
```
