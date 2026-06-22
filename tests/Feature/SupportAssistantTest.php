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

    public function test_it_degrades_when_ai_is_not_configured(): void
    {
        config(['ai.anthropic.key' => null]);

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
