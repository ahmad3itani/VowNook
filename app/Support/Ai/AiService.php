<?php

namespace App\Support\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin wrapper around the Anthropic Messages API (POST /v1/messages) using
 * Laravel's HTTP client. Generation is done with forced tool-use so the model
 * returns a single, schema-validated JSON object instead of free-form prose.
 *
 * The service degrades gracefully: when the feature is disabled or no API key
 * is configured, isConfigured() returns false and callers skip AI entirely.
 */
class AiService
{
    /**
     * Whether AI assistance is enabled and has the credentials it needs.
     */
    public function isConfigured(): bool
    {
        return (bool) config('ai.enabled')
            && config('ai.provider') === 'anthropic'
            && filled(config('ai.anthropic.key'));
    }

    /**
     * Ask the model to populate a single tool and return that tool's `input`
     * object as a PHP array — the structured result the caller asked for.
     *
     * @param  array<string,mixed>  $tool  Anthropic tool definition: name, description, input_schema.
     * @return array<string,mixed>
     *
     * @throws AiException
     */
    public function generateStructured(string $system, string $userPrompt, array $tool): array
    {
        if (! $this->isConfigured()) {
            throw new AiException('AI assistance is not configured.');
        }

        $response = $this->call([
            'model' => config('ai.model'),
            'max_tokens' => (int) config('ai.max_tokens'),
            'system' => $system,
            'tools' => [$tool],
            // Force the model to answer by calling our tool — guarantees the
            // response is a tool_use block matching input_schema, not prose.
            'tool_choice' => ['type' => 'tool', 'name' => $tool['name']],
            'messages' => [
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ]);

        return $this->extractToolInput($response, $tool['name']);
    }

    /**
     * Perform the HTTP call and translate transport/HTTP failures into a
     * single user-safe AiException (with technical detail logged).
     *
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     *
     * @throws AiException
     */
    protected function call(array $payload): array
    {
        try {
            $response = Http::withHeaders([
                'x-api-key' => config('ai.anthropic.key'),
                'anthropic-version' => config('ai.anthropic.version'),
                'content-type' => 'application/json',
            ])
                ->timeout((int) config('ai.timeout'))
                ->baseUrl(rtrim((string) config('ai.anthropic.base_url'), '/'))
                ->post('/v1/messages', $payload);
        } catch (ConnectionException $e) {
            Log::warning('AI request connection failed', ['message' => $e->getMessage()]);

            throw new AiException('The AI service is temporarily unreachable. Please try again.');
        } catch (Throwable $e) {
            Log::error('AI request failed unexpectedly', ['message' => $e->getMessage()]);

            throw new AiException('Something went wrong generating your plan. Please try again.');
        }

        if ($response->failed()) {
            Log::warning('AI request returned an error status', [
                'status' => $response->status(),
                'body' => $response->json('error.message') ?? $response->body(),
            ]);

            // 429/5xx are transient; everything else is a configuration/usage bug.
            $message = in_array($response->status(), [429, 500, 503, 529], true)
                ? 'The AI service is busy right now. Please try again in a moment.'
                : 'Something went wrong generating your plan. Please try again.';

            throw new AiException($message);
        }

        return (array) $response->json();
    }

    /**
     * Pull the named tool's `input` object out of the response content blocks.
     *
     * @param  array<string,mixed>  $response
     * @return array<string,mixed>
     *
     * @throws AiException
     */
    protected function extractToolInput(array $response, string $toolName): array
    {
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'tool_use' && ($block['name'] ?? null) === $toolName) {
                return (array) ($block['input'] ?? []);
            }
        }

        Log::warning('AI response did not contain the expected tool call', [
            'tool' => $toolName,
            'stop_reason' => $response['stop_reason'] ?? null,
        ]);

        throw new AiException('The AI did not return a usable result. Please try again.');
    }
}
