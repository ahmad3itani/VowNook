<?php

namespace Tests\Feature;

use App\Support\Ai\AiService;
use App\Support\Blog\AiBlogWriter;
use App\Support\Blog\BlogTopics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The per-purpose model tiers: each feature passes its tier's configured model
 * to the provider, and unset tiers fall back to the provider default — so
 * nothing changes until the env opts in.
 */
class AiModelTiersTest extends TestCase
{
    use RefreshDatabase;

    private function enableAi(): void
    {
        config([
            'ai.enabled' => true,
            'ai.provider' => 'anthropic',
            'ai.anthropic.key' => 'test-key',
            'ai.anthropic.base_url' => 'https://api.anthropic.com',
            'ai.anthropic.version' => '2023-06-01',
            'ai.openrouter.key' => null,
            'ai.model' => 'claude-default-model',
        ]);
    }

    private function fakeToolResponse(string $toolName, array $input): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'tool_use', 'name' => $toolName, 'input' => $input]],
            'stop_reason' => 'tool_use',
        ])]);
    }

    public function test_model_for_returns_configured_tier_or_null(): void
    {
        config(['ai.models.content' => 'anthropic/claude-fable-5', 'ai.models.chat' => null]);

        $ai = app(AiService::class);

        $this->assertSame('anthropic/claude-fable-5', $ai->modelFor('content'));
        $this->assertNull($ai->modelFor('chat'));
        $this->assertNull($ai->modelFor('structured'));
    }

    public function test_content_tier_model_is_sent_to_the_provider(): void
    {
        $this->enableAi();
        config([
            'ai.models.content' => 'claude-content-tier',
            'ai.blog_autopilot.min_words' => 5,
        ]);
        $this->fakeToolResponse('write_article', [
            'title' => 'A Title',
            'excerpt' => 'An excerpt.',
            'body_markdown' => 'Enough words to clear the tiny quality floor set above.',
            'meta_description' => 'Meta.',
        ]);

        app(AiBlogWriter::class)->write(BlogTopics::all()[0]);

        Http::assertSent(fn ($request) => $request['model'] === 'claude-content-tier');
    }

    public function test_unset_tier_falls_back_to_the_default_model(): void
    {
        $this->enableAi();
        config(['ai.models.content' => null, 'ai.blog_autopilot.min_words' => 5]);
        $this->fakeToolResponse('write_article', [
            'title' => 'A Title',
            'excerpt' => 'An excerpt.',
            'body_markdown' => 'Enough words to clear the tiny quality floor set above.',
            'meta_description' => 'Meta.',
        ]);

        app(AiBlogWriter::class)->write(BlogTopics::all()[0]);

        Http::assertSent(fn ($request) => $request['model'] === 'claude-default-model');
    }

    public function test_chat_accepts_a_model_override(): void
    {
        $this->enableAi();
        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'text', 'text' => 'Hello!']],
        ])]);

        app(AiService::class)->chat('system', [['role' => 'user', 'content' => 'hi']], 'claude-chat-tier');

        Http::assertSent(fn ($request) => $request['model'] === 'claude-chat-tier');
    }
}
