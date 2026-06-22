<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
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
