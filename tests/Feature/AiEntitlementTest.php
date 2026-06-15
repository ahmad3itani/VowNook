<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiEntitlementTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Wedding} */
    protected function ownerWithWedding(array $userAttrs = []): array
    {
        $user = User::factory()->create($userAttrs);
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
            'ai.model' => 'claude-sonnet-4-6',
        ]);
    }

    protected function fakeChecklist(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [[
                    'type' => 'tool_use',
                    'name' => 'propose_checklist',
                    'input' => ['tasks' => [
                        ['title' => 'Book the venue', 'category' => 'planning', 'priority' => 'high', 'months_before' => 12],
                    ]],
                ]],
                'stop_reason' => 'tool_use',
            ]),
        ]);
    }

    public function test_can_use_ai_mapping(): void
    {
        $this->assertFalse(User::factory()->make(['plan' => 'free', 'account_type' => 'couple', 'is_admin' => false])->canUseAi());
        $this->assertTrue(User::factory()->make(['plan' => 'premium', 'account_type' => 'couple', 'is_admin' => false])->canUseAi());
        $this->assertTrue(User::factory()->make(['plan' => 'free', 'account_type' => 'planner', 'is_admin' => false])->canUseAi());
        $this->assertTrue(User::factory()->make(['plan' => 'free', 'account_type' => 'couple', 'is_admin' => true])->canUseAi());
    }

    public function test_free_couple_cannot_generate(): void
    {
        [$user] = $this->ownerWithWedding(['plan' => 'free']);
        $this->enableAi();
        Http::fake();

        $this->actingAs($user)
            ->postJson('/assistant/generate', ['kind' => 'checklist'])
            ->assertStatus(403);

        Http::assertNothingSent();
    }

    public function test_free_couple_cannot_apply(): void
    {
        [$user] = $this->ownerWithWedding(['plan' => 'free']);

        $this->actingAs($user)->post('/assistant/apply', [
            'kind' => 'checklist',
            'items' => [['title' => 'X', 'category' => 'planning', 'priority' => 'low', 'months_before' => 1]],
        ])->assertForbidden();

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_premium_couple_can_generate(): void
    {
        [$user] = $this->ownerWithWedding(['plan' => 'premium']);
        $this->enableAi();
        $this->fakeChecklist();

        $this->actingAs($user)
            ->postJson('/assistant/generate', ['kind' => 'checklist'])
            ->assertOk()
            ->assertJsonPath('items.0.title', 'Book the venue');
    }

    public function test_planner_can_generate(): void
    {
        [$user] = $this->ownerWithWedding(['account_type' => 'planner', 'plan' => 'free']);
        $this->enableAi();
        $this->fakeChecklist();

        $this->actingAs($user)
            ->postJson('/assistant/generate', ['kind' => 'checklist', 'notes' => null])
            ->assertOk();
    }

    public function test_index_exposes_entitlement(): void
    {
        [$free] = $this->ownerWithWedding(['plan' => 'free']);
        $this->actingAs($free)->get('/assistant')
            ->assertOk()
            ->assertInertia(fn ($p) => $p->component('assistant/index')->where('entitled', false));

        [$premium] = $this->ownerWithWedding(['plan' => 'premium']);
        $this->actingAs($premium)->get('/assistant')
            ->assertInertia(fn ($p) => $p->where('entitled', true));
    }
}
