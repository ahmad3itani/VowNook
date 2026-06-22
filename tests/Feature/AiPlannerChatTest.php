<?php

namespace Tests\Feature;

use App\Models\AiChatMessage;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiPlannerChatTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Wedding} */
    protected function ownerWithWedding(string $plan = 'premium'): array
    {
        $user = User::factory()->create(['plan' => $plan]);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id, 'event_date' => '2026-09-12']);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    protected function enableAi(): void
    {
        config([
            'ai.enabled' => true,
            'ai.provider' => 'anthropic',
            'ai.anthropic.key' => 'test-key',
            'ai.anthropic.base_url' => 'https://api.anthropic.com',
            'ai.anthropic.version' => '2023-06-01',
            'ai.openrouter.key' => null,
            'ai.model' => 'claude-sonnet-4-6',
        ]);
    }

    protected function fakeReply(string $text): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => $text]],
                'stop_reason' => 'end_turn',
            ]),
        ]);
    }

    public function test_chat_returns_a_reply_and_persists_both_turns(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $this->enableAi();
        $this->fakeReply('Start by booking your **Venue**.');

        $this->actingAs($user)
            ->postJson('/assistant/chat', ['message' => 'Where do we start?'])
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('reply', 'Start by booking your **Venue**.');

        $this->assertDatabaseHas('ai_chat_messages', [
            'wedding_id' => $wedding->id, 'role' => 'user', 'content' => 'Where do we start?',
        ]);
        $this->assertDatabaseHas('ai_chat_messages', [
            'wedding_id' => $wedding->id, 'role' => 'assistant', 'content' => 'Start by booking your **Venue**.',
        ]);
    }

    public function test_chat_sends_prior_history_to_the_model(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $this->enableAi();
        AiChatMessage::create(['wedding_id' => $wedding->id, 'role' => 'user', 'content' => 'Our budget is 30k']);
        AiChatMessage::create(['wedding_id' => $wedding->id, 'role' => 'assistant', 'content' => 'Noted — $30k.']);
        $this->fakeReply('For 100 guests that works.');

        $this->actingAs($user)
            ->postJson('/assistant/chat', ['message' => 'And about 100 guests'])
            ->assertOk();

        Http::assertSent(function ($request) {
            $messages = $request['messages'] ?? [];

            return count($messages) === 3
                && $messages[0]['content'] === 'Our budget is 30k'
                && $messages[2]['role'] === 'user'
                && $messages[2]['content'] === 'And about 100 guests';
        });
    }

    public function test_chat_is_gated_to_paid_plans(): void
    {
        [$user] = $this->ownerWithWedding('free');
        $this->enableAi();
        Http::fake();

        $this->actingAs($user)
            ->postJson('/assistant/chat', ['message' => 'hi'])
            ->assertForbidden();

        Http::assertNothingSent();
        $this->assertDatabaseCount('ai_chat_messages', 0);
    }

    public function test_chat_degrades_gracefully_on_an_upstream_error(): void
    {
        [$user] = $this->ownerWithWedding();
        $this->enableAi();
        Http::fake(['api.anthropic.com/*' => Http::response('boom', 500)]);

        $this->actingAs($user)
            ->postJson('/assistant/chat', ['message' => 'help'])
            ->assertOk()
            ->assertJsonPath('ok', false);

        // A failed turn persists nothing — no orphaned half-conversation.
        $this->assertDatabaseCount('ai_chat_messages', 0);
    }

    public function test_chat_degrades_when_not_configured(): void
    {
        [$user] = $this->ownerWithWedding();
        config(['ai.enabled' => true, 'ai.anthropic.key' => null, 'ai.openrouter.key' => null]);
        Http::fake();

        $this->actingAs($user)
            ->postJson('/assistant/chat', ['message' => 'hi'])
            ->assertOk()
            ->assertJsonPath('available', false);

        Http::assertNothingSent();
    }

    public function test_reset_clears_the_conversation(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        AiChatMessage::create(['wedding_id' => $wedding->id, 'role' => 'user', 'content' => 'hi']);

        $this->actingAs($user)->delete('/assistant/chat')->assertRedirect();

        $this->assertDatabaseCount('ai_chat_messages', 0);
    }

    public function test_chat_stream_streams_deltas_and_persists_the_reply(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $this->enableAi();

        $sse = 'data: '.json_encode(['type' => 'content_block_delta', 'delta' => ['type' => 'text_delta', 'text' => 'Book ']])."\n\n"
            .'data: '.json_encode(['type' => 'content_block_delta', 'delta' => ['type' => 'text_delta', 'text' => 'the venue.']])."\n\n"
            .'data: '.json_encode(['type' => 'message_stop'])."\n\n";

        Http::fake(['api.anthropic.com/*' => Http::response($sse, 200, ['Content-Type' => 'text/event-stream'])]);

        $response = $this->actingAs($user)->post('/assistant/chat/stream', ['message' => 'Where do we start?']);
        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString('Book ', $content);
        $this->assertStringContainsString('the venue.', $content);
        $this->assertStringContainsString('"done":true', $content);

        // The full reply is assembled from the deltas and persisted once.
        $this->assertDatabaseHas('ai_chat_messages', [
            'wedding_id' => $wedding->id, 'role' => 'assistant', 'content' => 'Book the venue.',
        ]);
    }

    public function test_chat_stream_is_gated_to_paid_plans(): void
    {
        [$user] = $this->ownerWithWedding('free');
        $this->enableAi();
        Http::fake();

        $this->actingAs($user)
            ->post('/assistant/chat/stream', ['message' => 'hi'])
            ->assertForbidden();

        Http::assertNothingSent();
        $this->assertDatabaseCount('ai_chat_messages', 0);
    }

    public function test_index_exposes_chat_history(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $this->enableAi();
        AiChatMessage::create(['wedding_id' => $wedding->id, 'role' => 'assistant', 'content' => 'Hello there!']);

        $this->actingAs($user)
            ->get('/assistant')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('assistant/index')
                ->has('history', 1)
                ->where('history.0.content', 'Hello there!')
            );
    }
}
