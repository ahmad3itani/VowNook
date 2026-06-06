<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimelineWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function ownerWithWedding(): array
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_index_is_scoped_to_the_active_wedding(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        TimelineEvent::factory()->count(2)->create(['wedding_id' => $wedding->id]);
        TimelineEvent::factory()->count(3)->create();

        $this->actingAs($user)
            ->get('/timeline')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('timeline/index')
                ->has('events', 2)
            );
    }

    public function test_member_can_create_an_event_linked_to_a_vendor(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $vendor = Vendor::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)->post('/timeline', [
            'title' => 'First look',
            'type' => 'photos',
            'starts_at' => '2026-09-01 14:00:00',
            'ends_at' => '2026-09-01 14:45:00',
            'location' => 'Garden terrace',
            'vendor_id' => $vendor->id,
        ])->assertRedirect();

        $this->assertDatabaseHas('timeline_events', [
            'wedding_id' => $wedding->id,
            'title' => 'First look',
            'type' => 'photos',
            'location' => 'Garden terrace',
            'vendor_id' => $vendor->id,
        ]);
    }

    public function test_viewer_cannot_create_an_event(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);

        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($viewer)->post('/timeline', [
            'title' => 'Nope',
            'type' => 'other',
            'starts_at' => '2026-09-01 10:00:00',
        ])->assertForbidden();
    }

    public function test_cannot_update_an_event_from_another_wedding(): void
    {
        [$user] = $this->ownerWithWedding();
        $foreign = TimelineEvent::factory()->create();

        $this->actingAs($user)->put("/timeline/{$foreign->id}", [
            'title' => 'Hijack',
            'type' => 'other',
            'starts_at' => '2026-09-01 10:00:00',
        ])->assertNotFound();
    }

    public function test_invalid_type_is_rejected(): void
    {
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/timeline', [
            'title' => 'Bad',
            'type' => 'spaceship',
            'starts_at' => '2026-09-01 10:00:00',
        ])->assertSessionHasErrors('type');
    }

    public function test_end_time_must_not_precede_start_time(): void
    {
        [$user] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/timeline', [
            'title' => 'Backwards',
            'type' => 'reception',
            'starts_at' => '2026-09-01 18:00:00',
            'ends_at' => '2026-09-01 17:00:00',
        ])->assertSessionHasErrors('ends_at');
    }

    public function test_vendor_must_belong_to_the_wedding(): void
    {
        [$user] = $this->ownerWithWedding();
        $foreignVendor = Vendor::factory()->create();

        $this->actingAs($user)->post('/timeline', [
            'title' => 'Outside vendor',
            'type' => 'other',
            'starts_at' => '2026-09-01 10:00:00',
            'vendor_id' => $foreignVendor->id,
        ])->assertSessionHasErrors('vendor_id');
    }

    public function test_stats_are_computed(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $vendor = Vendor::factory()->create(['wedding_id' => $wedding->id]);

        TimelineEvent::factory()->create([
            'wedding_id' => $wedding->id,
            'vendor_id' => $vendor->id,
            'starts_at' => '2026-09-01 10:00:00',
            'location' => 'Chapel',
        ]);
        TimelineEvent::factory()->create([
            'wedding_id' => $wedding->id,
            'vendor_id' => null,
            'starts_at' => '2026-09-02 12:00:00',
            'location' => 'Chapel',
        ]);

        $this->actingAs($user)
            ->get('/timeline')
            ->assertInertia(fn ($page) => $page
                ->where('stats.total', 2)
                ->where('stats.linked', 1)
                ->where('stats.locations', 1)
                ->where('stats.days', 2)
            );
    }

    public function test_member_can_delete_an_event(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $event = TimelineEvent::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)
            ->delete("/timeline/{$event->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('timeline_events', ['id' => $event->id]);
    }
}
