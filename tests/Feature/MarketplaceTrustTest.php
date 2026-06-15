<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Models\User;
use App\Models\VendorProfile;
use App\Notifications\ReportFiled;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MarketplaceTrustTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_report_a_vendor_listing_and_admins_are_notified(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['is_admin' => true]);
        $reporter = User::factory()->create();
        $profile = VendorProfile::factory()->create(['status' => 'published']);

        $this->actingAs($reporter)->post('/report', [
            'type' => 'vendor',
            'id' => $profile->slug,
            'reason' => 'fake_or_scam',
            'details' => 'Stolen photos.',
        ])->assertRedirect();

        $this->assertDatabaseHas('reports', [
            'reportable_type' => VendorProfile::class,
            'reportable_id' => $profile->id,
            'reason' => 'fake_or_scam',
            'status' => 'open',
        ]);
        Notification::assertSentTo($admin, ReportFiled::class);
    }

    public function test_admin_can_view_and_resolve_reports_but_others_cannot(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = VendorProfile::factory()->create(['status' => 'published']);
        $report = $profile->morphMany(Report::class, 'reportable')->create(['reason' => 'spam']);

        $this->actingAs(User::factory()->create())->get('/admin/reports')->assertForbidden();

        $this->actingAs($admin)->get('/admin/reports')->assertOk();
        $this->actingAs($admin)->put("/admin/reports/{$report->id}", ['status' => 'actioned'])->assertRedirect();
        $this->assertSame('actioned', $report->fresh()->status);
    }

    public function test_admin_can_toggle_a_verified_badge(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $profile = VendorProfile::factory()->create();

        $this->actingAs($admin)->patch("/admin/vendors/{$profile->slug}/verify")->assertRedirect();
        $this->assertNotNull($profile->fresh()->verified_at);

        $this->actingAs($admin)->patch("/admin/vendors/{$profile->slug}/verify")->assertRedirect();
        $this->assertNull($profile->fresh()->verified_at);
    }

    public function test_submitting_for_review_records_agreement_acceptance(): void
    {
        $user = User::factory()->create(['account_type' => 'vendor']);
        $profile = VendorProfile::factory()->create(['user_id' => $user->id, 'status' => 'draft']);

        $this->actingAs($user)->post('/vendor/profile/submit')->assertRedirect();

        $profile->refresh();
        $this->assertNotNull($profile->agreement_accepted_at);
        $this->assertSame('pending_review', $profile->status->value);
    }

    public function test_policy_pages_are_public_and_indexable(): void
    {
        foreach (['/marketplace-rules', '/vendor-agreement'] as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $this->assertStringNotContainsString('noindex', $html);
        }
    }
}
