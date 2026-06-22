<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Ai\AiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class SupportAssistantTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_answers_a_question_when_ai_is_configured(): void
    {
        config(['ai.enabled' => true, 'ai.provider' => 'anthropic', 'ai.anthropic.key' => 'test-key']);

        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [[
                    'type' => 'tool_use',
                    'name' => 'provide_help',
                    'input' => ['answer' => 'Open Guests and click Add guest.', 'confident' => true],
                ]],
                'stop_reason' => 'tool_use',
            ], 200),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/support/ask', ['question' => 'How do I add guests?'])
            ->assertOk()
            ->assertJson([
                'available' => true,
                'answer' => 'Open Guests and click Add guest.',
                'confident' => true,
            ]);
    }

    public function test_it_answers_via_openrouter_when_the_key_is_an_openrouter_key(): void
    {
        // An OpenRouter key (sk-or-…) put in ANTHROPIC_API_KEY is auto-detected
        // and routed to OpenRouter's OpenAI-compatible endpoint.
        config(['ai.enabled' => true, 'ai.anthropic.key' => 'sk-or-v1-test']);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'role' => 'assistant',
                        'tool_calls' => [[
                            'id' => 'call_1',
                            'type' => 'function',
                            'function' => [
                                'name' => 'provide_help',
                                'arguments' => json_encode(['answer' => 'Open Settings → Plan to upgrade.', 'confident' => true]),
                            ],
                        ]],
                    ],
                    'finish_reason' => 'tool_calls',
                ]],
            ], 200),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/support/ask', ['question' => 'How do I upgrade?'])
            ->assertOk()
            ->assertJson([
                'available' => true,
                'answer' => 'Open Settings → Plan to upgrade.',
                'confident' => true,
            ]);
    }

    public function test_an_upstream_error_degrades_to_a_friendly_json_answer(): void
    {
        // A provider error (here a 500) must come back as a graceful 200 JSON
        // message — never a hard failure the front-end can't parse.
        config(['ai.enabled' => true, 'ai.anthropic.key' => 'test-key', 'ai.openrouter.key' => null]);

        Http::fake(['api.anthropic.com/*' => Http::response('upstream boom', 500)]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/support/ask', ['question' => 'How do I share the website?'])
            ->assertOk()
            ->assertJson(['available' => true, 'confident' => false]);
    }

    public function test_it_never_returns_a_non_json_500_on_an_unexpected_error(): void
    {
        // Even an unexpected (non-AiException) failure stays a parseable 200 JSON,
        // so the help bot can always point the person at the request form.
        config(['ai.enabled' => true, 'ai.anthropic.key' => 'test-key']);

        $this->mock(AiService::class, function ($mock) {
            $mock->shouldReceive('isConfigured')->andReturn(true);
            $mock->shouldReceive('generateStructured')->andThrow(new RuntimeException('boom'));
        });

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/support/ask', ['question' => 'How do I share the website?'])
            ->assertOk()
            ->assertJson(['available' => true, 'confident' => false]);
    }

    public function test_it_degrades_when_ai_is_not_configured(): void
    {
        config(['ai.anthropic.key' => null, 'ai.openrouter.key' => null]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/support/ask', ['question' => 'anything'])
            ->assertOk()
            ->assertJson(['available' => false]);
    }

    public function test_it_requires_authentication(): void
    {
        // Web auth group redirects guests to login (JSON errors are api/* only).
        $this->post('/support/ask', ['question' => 'hi'])->assertRedirect('/login');
    }
}
