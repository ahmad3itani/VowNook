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

    /** The model that will actually be used for the active provider. */
    public function model(): string
    {
        return $this->provider() === 'openrouter'
            ? (string) config('ai.openrouter.model')
            : (string) config('ai.model');
    }

    /**
     * Make a tiny live call and report the raw outcome for diagnostics — unlike
     * generateStructured(), this surfaces the provider's exact status + error
     * body (e.g. "model not found", "insufficient credits", "invalid key") so a
     * misconfiguration can be pinpointed. Never returns the API key.
     *
     * @return array{ok: bool, status: int|null, error: string|null}
     */
    public function ping(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'status' => null, 'error' => 'AI is not configured (no key).'];
        }

        $openRouter = $this->provider() === 'openrouter';

        $url = $openRouter
            ? rtrim((string) config('ai.openrouter.base_url'), '/').'/chat/completions'
            : rtrim((string) config('ai.anthropic.base_url'), '/').'/v1/messages';

        $headers = $openRouter
            ? ['authorization' => 'Bearer '.$this->apiKey()]
            : ['x-api-key' => $this->apiKey(), 'anthropic-version' => config('ai.anthropic.version')];

        $payload = $openRouter
            ? ['model' => $this->model(), 'max_tokens' => 5, 'messages' => [['role' => 'user', 'content' => 'ping']]]
            : ['model' => $this->model(), 'max_tokens' => 5, 'messages' => [['role' => 'user', 'content' => 'ping']]];

        try {
            $response = Http::withHeaders($headers)->timeout(20)->post($url, $payload);
        } catch (Throwable $e) {
            return ['ok' => false, 'status' => null, 'error' => $e->getMessage()];
        }

        if ($response->successful()) {
            return ['ok' => true, 'status' => $response->status(), 'error' => null];
        }

        return [
            'ok' => false,
            'status' => $response->status(),
            'error' => $response->json('error.message') ?? mb_substr($response->body(), 0, 300),
        ];
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

    /**
     * Hold a free-form, multi-turn conversation and return the assistant's text
     * reply — the chat planner's path (distinct from generateStructured, which
     * forces a single JSON tool result).
     *
     * @param  array<int, array{role:string, content:string}>  $messages
     *
     * @throws AiException
     */
    public function chat(string $system, array $messages): string
    {
        if (! $this->isConfigured()) {
            throw new AiException('AI assistance is not configured.');
        }

        return $this->provider() === 'openrouter'
            ? $this->chatViaOpenRouter($system, $messages)
            : $this->chatViaAnthropic($system, $messages);
    }

    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     *
     * @throws AiException
     */
    protected function chatViaAnthropic(string $system, array $messages): string
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
                'messages' => $messages,
            ],
        );

        $text = '';
        foreach ($response['content'] ?? [] as $block) {
            if (($block['type'] ?? null) === 'text') {
                $text .= (string) ($block['text'] ?? '');
            }
        }

        return $this->ensureText($text);
    }

    /**
     * @param  array<int, array{role:string, content:string}>  $messages
     *
     * @throws AiException
     */
    protected function chatViaOpenRouter(string $system, array $messages): string
    {
        $response = $this->post(
            config('ai.openrouter.base_url'),
            '/chat/completions',
            [
                'authorization' => 'Bearer '.$this->apiKey(),
                'content-type' => 'application/json',
                'http-referer' => (string) config('app.url'),
                'x-title' => (string) config('app.name'),
            ],
            [
                'model' => config('ai.openrouter.model'),
                'max_tokens' => (int) config('ai.max_tokens'),
                'messages' => array_merge(
                    [['role' => 'system', 'content' => $system]],
                    $messages,
                ),
            ],
        );

        return $this->ensureText((string) ($response['choices'][0]['message']['content'] ?? ''));
    }

    /** @throws AiException */
    protected function ensureText(string $text): string
    {
        $text = trim($text);

        if ($text === '') {
            throw new AiException('The AI did not return a usable answer. Please try again.');
        }

        return $text;
    }

    // ── Streaming chat ───────────────────────────────────────────────────────

    /**
     * Stream a multi-turn conversation, calling $onDelta with each text chunk as
     * it arrives, and return the full accumulated reply. Same two providers as
     * chat(); both speak Server-Sent Events, which we parse line by line.
     *
     * @param  array<int, array{role:string, content:string}>  $messages
     * @param  callable(string):void  $onDelta
     *
     * @throws AiException
     */
    public function streamChat(string $system, array $messages, callable $onDelta): string
    {
        if (! $this->isConfigured()) {
            throw new AiException('AI assistance is not configured.');
        }

        $openRouter = $this->provider() === 'openrouter';

        $url = $openRouter
            ? rtrim((string) config('ai.openrouter.base_url'), '/').'/chat/completions'
            : rtrim((string) config('ai.anthropic.base_url'), '/').'/v1/messages';

        $headers = $openRouter
            ? [
                'authorization' => 'Bearer '.$this->apiKey(),
                'content-type' => 'application/json',
                'http-referer' => (string) config('app.url'),
                'x-title' => (string) config('app.name'),
            ]
            : [
                'x-api-key' => $this->apiKey(),
                'anthropic-version' => config('ai.anthropic.version'),
                'content-type' => 'application/json',
            ];

        $payload = $openRouter
            ? [
                'model' => config('ai.openrouter.model'),
                'max_tokens' => (int) config('ai.max_tokens'),
                'stream' => true,
                'messages' => array_merge([['role' => 'system', 'content' => $system]], $messages),
            ]
            : [
                'model' => config('ai.model'),
                'max_tokens' => (int) config('ai.max_tokens'),
                'stream' => true,
                'system' => $system,
                'messages' => $messages,
            ];

        try {
            $response = Http::withHeaders($headers)
                ->withOptions(['stream' => true])
                ->timeout((int) config('ai.timeout'))
                ->post($url, $payload);
        } catch (ConnectionException $e) {
            Log::warning('AI stream connection failed', ['message' => $e->getMessage()]);

            throw new AiException('The AI service is temporarily unreachable. Please try again.');
        } catch (Throwable $e) {
            Log::error('AI stream failed unexpectedly', ['message' => $e->getMessage()]);

            throw new AiException('Something went wrong. Please try again.');
        }

        if ($response->failed()) {
            Log::warning('AI stream returned an error status', ['status' => $response->status()]);

            throw new AiException(in_array($response->status(), [429, 500, 503, 529], true)
                ? 'The AI service is busy right now. Please try again in a moment.'
                : 'Something went wrong. Please try again.');
        }

        $full = '';
        $buffer = '';
        $body = $response->toPsrResponse()->getBody();

        while (! $body->eof()) {
            $chunk = $body->read(2048);

            if ($chunk === '') {
                continue;
            }

            $buffer .= $chunk;

            while (($nl = strpos($buffer, "\n")) !== false) {
                $line = rtrim(substr($buffer, 0, $nl), "\r");
                $buffer = substr($buffer, $nl + 1);

                $text = $this->parseStreamLine($line, $openRouter);

                if ($text !== null && $text !== '') {
                    $full .= $text;
                    $onDelta($text);
                }
            }
        }

        return $this->ensureText($full);
    }

    /** Pull the text delta out of one SSE line, or null for non-text/control lines. */
    protected function parseStreamLine(string $line, bool $openRouter): ?string
    {
        if (! str_starts_with($line, 'data:')) {
            return null;
        }

        $data = trim(substr($line, 5));

        if ($data === '' || $data === '[DONE]') {
            return null;
        }

        $json = json_decode($data, true);

        if (! is_array($json)) {
            return null;
        }

        if ($openRouter) {
            return $json['choices'][0]['delta']['content'] ?? null;
        }

        if (($json['type'] ?? null) === 'content_block_delta'
            && ($json['delta']['type'] ?? null) === 'text_delta') {
            return $json['delta']['text'] ?? null;
        }

        return null;
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
