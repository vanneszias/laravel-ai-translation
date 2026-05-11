<?php

declare(strict_types=1);

namespace Statikbe\AiTranslation\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * Laravel AI Agent for translating a batch of translation keys.
 *
 * Receives a JSON-encoded map of { key: "source text" } and returns
 * a structured JSON map of { key: "translated text" }.
 *
 * The batch approach minimises API calls and cost — an entire translation
 * group is translated in a single LLM request.
 */
class TranslationAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    /**
     * @param  string        $systemPrompt  The configured system prompt for this translation.
     * @param  list<string>  $keys          The translation keys we expect back (for schema building).
     * @param  string        $from          Source locale.
     * @param  string        $to            Target locale.
     */
    public function __construct(
        protected string $systemPrompt,
        protected array $keys,
        protected string $from,
        protected string $to,
    ) {}

    /**
     * Instructions sent as the system prompt to the LLM.
     */
    public function instructions(): Stringable|string
    {
        return (
            $this->systemPrompt
            . "\n\nYou are translating from locale '{$this->from}' to locale '{$this->to}'."
            . "\nReturn a JSON object where each key maps to the translated string."
            . "\nDo not change, add, or remove keys."
        );
    }

    /**
     * Define the structured output schema dynamically based on the input keys.
     *
     * Each key in the batch maps to a required string in the schema.
     * This ensures the LLM returns exactly the keys we need.
     */
    public function schema(JsonSchema $schema): array
    {
        $properties = [];

        foreach ($this->keys as $key) {
            $properties[$key] = $schema->string()->required();
        }

        return $properties;
    }
}
