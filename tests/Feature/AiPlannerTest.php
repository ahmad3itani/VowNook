<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\BudgetCategory;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AiPlannerTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Wedding} */
    protected function ownerWithWedding(?string $eventDate = '2026-09-12'): array
    {
        // Premium so the AI entitlement gate passes — these tests cover the
        // generation/apply mechanics, not the paywall (see AiEntitlementTest).
        $user = User::factory()->create(['plan' => 'premium']);
        $wedding = Wedding::factory()->create([
            'owner_id' => $user->id,
            'event_date' => $eventDate,
        ]);
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

    /** Build a fake Anthropic tool_use response. */
    protected function fakeTool(string $toolName, array $input): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [
                    ['type' => 'tool_use', 'name' => $toolName, 'input' => $input],
                ],
                'stop_reason' => 'tool_use',
            ]),
        ]);
    }

    // ── Generate ───────────────────────────────────────────────────────────────

    public function test_generate_returns_a_checklist_and_sends_correct_anthropic_headers(): void
    {
        [$user] = $this->ownerWithWedding();
        $this->enableAi();
        $this->fakeTool('propose_checklist', [
            'tasks' => [
                ['title' => 'Book the venue', 'category' => 'planning', 'priority' => 'high', 'months_before' => 12],
                ['title' => 'Order the cake', 'category' => 'reception', 'priority' => 'medium', 'months_before' => 3],
            ],
        ]);

        $this->actingAs($user)
            ->postJson('/assistant/generate', ['kind' => 'checklist'])
            ->assertOk()
            ->assertJsonPath('kind', 'checklist')
            ->assertJsonCount(2, 'items')
            ->assertJsonPath('items.0.title', 'Book the venue');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/v1/messages')
            && $request->hasHeader('x-api-key', 'test-key')
            && $request->hasHeader('anthropic-version', '2023-06-01')
            && $request['tool_choice']['name'] === 'propose_checklist');
    }

    public function test_generate_budget_normalizes_dollars_to_cents(): void
    {
        [$user] = $this->ownerWithWedding();
        $this->enableAi();
        $this->fakeTool('propose_budget', [
            'items' => [
                ['category' => 'Venue', 'name' => 'Reception hall', 'estimated_dollars' => 8000],
            ],
        ]);

        $this->actingAs($user)
            ->postJson('/assistant/generate', ['kind' => 'budget', 'total_budget' => 35000])
            ->assertOk()
            ->assertJsonPath('items.0.estimated_cents', 800000);
    }

    public function test_generate_timeline_normalizes_loose_time(): void
    {
        [$user] = $this->ownerWithWedding();
        $this->enableAi();
        $this->fakeTool('propose_timeline', [
            'events' => [
                ['title' => 'First look', 'type' => 'photos', 'time' => '2:30 PM'],
            ],
        ]);

        $this->actingAs($user)
            ->postJson('/assistant/generate', ['kind' => 'timeline'])
            ->assertOk()
            ->assertJsonPath('items.0.time', '14:30');
    }

    public function test_generate_degrades_gracefully_when_not_configured(): void
    {
        [$user] = $this->ownerWithWedding();
        config(['ai.enabled' => true, 'ai.anthropic.key' => null, 'ai.openrouter.key' => null]);
        Http::fake();

        $this->actingAs($user)
            ->postJson('/assistant/generate', ['kind' => 'checklist'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'AI assistance is not configured on this server.');

        Http::assertNothingSent();
    }

    public function test_generate_rejects_an_unknown_kind(): void
    {
        [$user] = $this->ownerWithWedding();
        $this->enableAi();

        $this->actingAs($user)
            ->postJson('/assistant/generate', ['kind' => 'spaceship'])
            ->assertStatus(422);
    }

    public function test_viewer_cannot_generate(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);
        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();
        $this->enableAi();
        Http::fake();

        $this->actingAs($viewer)
            ->postJson('/assistant/generate', ['kind' => 'checklist'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    // ── Apply (no AI; pure persistence of couple-reviewed data) ─────────────────

    public function test_apply_checklist_inserts_tasks_with_due_dates_from_the_event_date(): void
    {
        [$user, $wedding] = $this->ownerWithWedding('2026-09-12');

        $this->actingAs($user)->post('/assistant/apply', [
            'kind' => 'checklist',
            'items' => [
                ['title' => 'Book the venue', 'category' => 'planning', 'priority' => 'high', 'months_before' => 12],
                ['title' => 'Final headcount', 'category' => 'logistics', 'priority' => 'medium', 'months_before' => 0],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('tasks', [
            'wedding_id' => $wedding->id,
            'title' => 'Book the venue',
            'category' => 'planning',
            'priority' => 'high',
            'due_date' => '2025-09-12 00:00:00',
        ]);
        $this->assertDatabaseHas('tasks', [
            'wedding_id' => $wedding->id,
            'title' => 'Final headcount',
            'due_date' => '2026-09-12 00:00:00',
        ]);
    }

    public function test_apply_budget_creates_categories_and_items(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/assistant/apply', [
            'kind' => 'budget',
            'items' => [
                ['category' => 'Venue', 'name' => 'Reception hall', 'estimated_cents' => 800000],
                ['category' => 'Venue', 'name' => 'Ceremony site', 'estimated_cents' => 150000],
                ['category' => 'Catering', 'name' => 'Dinner', 'estimated_cents' => 1200000],
            ],
        ])->assertRedirect();

        // "Venue" is reused, not duplicated, across its two line items.
        $this->assertDatabaseCount('budget_categories', 2);
        $this->assertDatabaseHas('budget_categories', ['wedding_id' => $wedding->id, 'name' => 'Venue']);
        $this->assertDatabaseHas('budget_items', [
            'wedding_id' => $wedding->id,
            'name' => 'Dinner',
            'estimated_cents' => 1200000,
        ]);
        $this->assertDatabaseCount('budget_items', 3);
    }

    public function test_apply_budget_reuses_an_existing_category_by_name(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        BudgetCategory::create(['wedding_id' => $wedding->id, 'name' => 'Venue', 'sort_order' => 1]);

        $this->actingAs($user)->post('/assistant/apply', [
            'kind' => 'budget',
            'items' => [
                ['category' => 'Venue', 'name' => 'Reception hall', 'estimated_cents' => 800000],
            ],
        ])->assertRedirect();

        $this->assertDatabaseCount('budget_categories', 1);
    }

    public function test_apply_timeline_inserts_events_anchored_to_the_wedding_date(): void
    {
        [$user, $wedding] = $this->ownerWithWedding('2026-09-12');

        $this->actingAs($user)->post('/assistant/apply', [
            'kind' => 'timeline',
            'items' => [
                ['title' => 'Ceremony', 'type' => 'ceremony', 'time' => '15:00', 'location' => 'Garden'],
            ],
        ])->assertRedirect();

        $event = TimelineEvent::where('wedding_id', $wedding->id)->firstOrFail();
        $this->assertSame('Ceremony', $event->title);
        $this->assertSame('2026-09-12 15:00', $event->starts_at->format('Y-m-d H:i'));
        $this->assertSame('Garden', $event->location);
    }

    public function test_apply_rejects_an_invalid_timeline_time(): void
    {
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/assistant/apply', [
            'kind' => 'timeline',
            'items' => [
                ['title' => 'Ceremony', 'type' => 'ceremony', 'time' => '25:99'],
            ],
        ])->assertSessionHasErrors('items.0.time');
    }

    public function test_apply_rejects_an_invalid_task_category(): void
    {
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/assistant/apply', [
            'kind' => 'checklist',
            'items' => [
                ['title' => 'Bad', 'category' => 'spaceship', 'priority' => 'low', 'months_before' => 1],
            ],
        ])->assertSessionHasErrors('items.0.category');
    }

    public function test_viewer_cannot_apply(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);
        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($viewer)->post('/assistant/apply', [
            'kind' => 'checklist',
            'items' => [
                ['title' => 'Nope', 'category' => 'other', 'priority' => 'low', 'months_before' => 1],
            ],
        ])->assertForbidden();

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_index_renders_with_configured_flag(): void
    {
        [$user] = $this->ownerWithWedding();
        $this->enableAi();

        $this->actingAs($user)
            ->get('/assistant')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('assistant/index')
                ->where('configured', true)
                ->where('can.checklist', true)
            );
    }

    // ── Extract (chat reply → editable items) ───────────────────────────────────

    public function test_extract_turns_chat_text_into_checklist_items(): void
    {
        [$user] = $this->ownerWithWedding();
        $this->enableAi();
        $this->fakeTool('propose_checklist', [
            'tasks' => [
                ['title' => 'Book the venue', 'category' => 'planning', 'priority' => 'high', 'months_before' => 12],
            ],
        ]);

        $this->actingAs($user)
            ->postJson('/assistant/extract', [
                'kind' => 'checklist',
                'text' => 'A few things: you should book your venue and order the cake.',
            ])
            ->assertOk()
            ->assertJsonPath('kind', 'checklist')
            ->assertJsonPath('items.0.title', 'Book the venue');
    }

    public function test_extract_is_paid_gated(): void
    {
        $user = User::factory()->create(['plan' => 'free']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();
        $this->enableAi();
        Http::fake();

        $this->actingAs($user)
            ->postJson('/assistant/extract', ['kind' => 'checklist', 'text' => 'book venue'])
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_viewer_cannot_extract(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);
        $viewer = User::factory()->create(['plan' => 'premium']);
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();
        $this->enableAi();
        Http::fake();

        $this->actingAs($viewer)
            ->postJson('/assistant/extract', ['kind' => 'checklist', 'text' => 'book venue'])
            ->assertForbidden();

        Http::assertNothingSent();
    }
}
