<?php

namespace App\Support\Ai;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin wrapper around a chat-completions API using Laravel's HTTP client.
 * Generation is done with forced tool/function use so the model returns a
 * single, schema-validated JSON object instead of free-form prose.
 *
 * Two providers are supported and auto-detected from the API key prefix:
 *   - Anthropic Messages API   (keys `sk-ant-…`, the default)
 *   - OpenRouter (OpenAI-style) (keys `sk-or-…`)
 * The key may live in either ANTHROPIC_API_KEY or OPENROUTER_API_KEY.
 *
 * Degrades gracefully: when disabled or no key is configured, isConfigured()
 * returns false and callers skip AI entirely.
 */
class AiService
{
    public function isConfigured(): bool
    {
        return (bool) config('ai.enabled') && filled($this->apiKey());
    }

    /** The configured key, from either provider's env var. */
    protected function apiKey(): ?string
    {
        return config('ai.anthropic.key') ?: config('ai.openrouter.key');
    }

    /** Which API to speak, inferred from the key prefix. */
    public function provider(): string
    {
        return str_starts_with((string) $this->apiKey(), 'sk-or-') ? 'openrouter' : 'anthropic';
    }

    /**
     * Ask the model to populate a single tool and return that tool's input as a
     * PHP array — the structured result the caller asked for.
     *
     * @param  array<string,mixed>  $tool  Tool definition: name, description, input_schema.
     * @return array<string,mixed>
     *
     * @throws AiException
     */
    public function generateStructured(string $system, string $userPrompt, array $tool): array
    {
        if (! $this->isConfigured()) {
            throw new AiException('AI assistance is not configured.');
        }

        return $this->provider() === 'openrouter'
            ? $this->viaOpenRouter($system, $userPrompt, $tool)
            : $this->viaAnthropic($system, $userPrompt, $tool);
    }

    // ── Anthropic (Messages API) ─────────────────────────────────────────────

    /**
     * @param  array<string,mixed>  $tool
     * @return array<string,mixed>
     *
     * @throws AiException
     */
    protected function viaAnthropic(string $system, string $userPrompt, array $tool): array
    {
        $response = $this->post(
            config('ai.anthropic.base_url'),
            '/v1/messages',
            [
                'x-api-key' => $this->apiKey(),
                'anthropic-version' => config('ai.anthropic.version'),
                'content-type' => 'application/json',
            ],
            [
                'model' => config('ai.model'),
                'max_tokens' => (int) config('ai.max_tokens'),
                'system' => $system,
                'tools' => [$tool],
                'tool_choice' => ['type' => 'tool', 'name' => $tool['name']],
                'messages' => [['role' => 'user', 'content' => $userPrompt]],
            ],
        );

        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'tool_use' && ($block['name'] ?? null) === $tool['name']) {
                return (array) ($block['input'] ?? []);
            }
        }

        return $this->noToolCall($tool['name'], $response['stop_reason'] ?? null);
    }

    // ── OpenRouter (OpenAI-compatible) ───────────────────────────────────────

    /**
     * @param  array<string,mixed>  $tool
     * @return array<string,mixed>
     *
     * @throws AiException
     */
    protected function viaOpenRouter(string $system, string $userPrompt, array $tool): array
    {
        $response = $this->post(
            config('ai.openrouter.base_url'),
            '/chat/completions',
            [
                'authorization' => 'Bearer '.$this->apiKey(),
                'content-type' => 'application/json',
                // OpenRouter attribution headers (optional but recommended).
                'http-referer' => (string) config('app.url'),
                'x-title' => (string) config('app.name'),
            ],
            [
                'model' => config('ai.openrouter.model'),
                'max_tokens' => (int) config('ai.max_tokens'),
                'messages' => [
                    ['role' => 'system', 'content' => $system],
                    ['role' => 'user', 'content' => $userPrompt],
                ],
                'tools' => [[
                    'type' => 'function',
                    'function' => [
                        'name' => $tool['name'],
                        'description' => $tool['description'] ?? '',
                        'parameters' => $tool['input_schema'] ?? ['type' => 'object'],
                    ],
                ]],
                'tool_choice' => ['type' => 'function', 'function' => ['name' => $tool['name']]],
            ],
        );

        $call = $response['choices'][0]['message']['tool_calls'][0] ?? null;

        if ($call && ($call['function']['name'] ?? null) === $tool['name']) {
            $args = $call['function']['arguments'] ?? '{}';
            $decoded = is_string($args) ? json_decode($args, true) : $args;

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $this->noToolCall($tool['name'], $response['choices'][0]['finish_reason'] ?? null);
    }

    // ── Shared HTTP + error handling ─────────────────────────────────────────

    /**
     * Perform the HTTP call and translate transport/HTTP failures into a single
     * user-safe AiException (with technical detail logged).
     *
     * @param  array<string,string>  $headers
     * @param  array<string,mixed>  $payload
     * @return array<string,mixed>
     *
     * @throws AiException
     */
    protected function post(string $baseUrl, string $endpoint, array $headers, array $payload): array
    {
        try {
            $response = Http::withHeaders($headers)
                ->timeout((int) config('ai.timeout'))
                ->baseUrl(rtrim((string) $baseUrl, '/'))
                ->post($endpoint, $payload);
        } catch (ConnectionException $e) {
            Log::warning('AI request connection failed', ['message' => $e->getMessage()]);

            throw new AiException('The AI service is temporarily unreachable. Please try again.');
        } catch (Throwable $e) {
            Log::error('AI request failed unexpectedly', ['message' => $e->getMessage()]);

            throw new AiException('Something went wrong. Please try again.');
        }

        if ($response->failed()) {
            Log::warning('AI request returned an error status', [
                'status' => $response->status(),
                'body' => $response->json('error.message') ?? $response->body(),
            ]);

            $message = in_array($response->status(), [429, 500, 503, 529], true)
                ? 'The AI service is busy right now. Please try again in a moment.'
                : 'Something went wrong. Please try again.';

            throw new AiException($message);
        }

        return (array) $response->json();
    }

    /**
     * @return never
     *
     * @throws AiException
     */
    protected function noToolCall(string $toolName, ?string $reason): array
    {
        Log::warning('AI response did not contain the expected tool call', [
            'tool' => $toolName,
            'reason' => $reason,
        ]);

        throw new AiException('The AI did not return a usable result. Please try again.');
    }
}
