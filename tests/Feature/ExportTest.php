<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\BudgetItem;
use App\Models\Guest;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportTest extends TestCase
{
    use RefreshDatabase;

    protected function ownerWithWedding(): array
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_guest_csv_lists_only_the_active_weddings_guests(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        Guest::factory()->create([
            'wedding_id' => $wedding->id,
            'first_name' => 'Amelia',
            'last_name' => 'Stone',
        ]);
        Guest::factory()->create(['first_name' => 'Outsider', 'last_name' => 'Nope']);

        $response = $this->actingAs($user)->get('/exports/guests');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $body = $response->streamedContent();
        $this->assertStringContainsString('Amelia', $body);
        $this->assertStringContainsString('First name', $body);
        $this->assertStringNotContainsString('Outsider', $body);
    }

    public function test_budget_csv_includes_amounts_in_dollars(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        BudgetItem::factory()->create([
            'wedding_id' => $wedding->id,
            'name' => 'Florist',
            'estimated_cents' => 150000,
        ]);

        $response = $this->actingAs($user)->get('/exports/budget');

        $response->assertOk();
        $body = $response->streamedContent();
        $this->assertStringContainsString('Florist', $body);
        $this->assertStringContainsString('1500.00', $body);
    }

    public function test_timeline_ics_contains_events_as_vevents(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        TimelineEvent::factory()->create([
            'wedding_id' => $wedding->id,
            'title' => 'First Dance',
            'starts_at' => '2026-09-12 19:30:00',
            'ends_at' => '2026-09-12 19:45:00',
        ]);

        $response = $this->actingAs($user)->get('/exports/timeline');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/calendar; charset=UTF-8');

        $body = $response->getContent();
        $this->assertStringContainsString('BEGIN:VCALENDAR', $body);
        $this->assertStringContainsString('BEGIN:VEVENT', $body);
        $this->assertStringContainsString('SUMMARY:First Dance', $body);
        $this->assertStringContainsString('DTSTART:', $body);
    }

    public function test_guest_pdf_is_downloadable(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        Guest::factory()->create(['wedding_id' => $wedding->id, 'first_name' => 'Amelia']);

        $response = $this->actingAs($user)->get('/exports/guests/pdf');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_timeline_pdf_is_downloadable(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        TimelineEvent::factory()->create([
            'wedding_id' => $wedding->id,
            'title' => 'First Dance',
            'starts_at' => '2026-09-12 19:30:00',
        ]);

        $response = $this->actingAs($user)->get('/exports/timeline/pdf');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_viewer_without_budget_read_cannot_export_budget(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);

        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();

        // Viewers have no budget access in the permission matrix.
        $this->actingAs($viewer)->get('/exports/budget')->assertForbidden();
    }
}
