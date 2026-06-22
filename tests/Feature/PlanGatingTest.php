<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wedding;
use App\Support\PlanFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanGatingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Wedding} */
    private function coupleOwner(string $plan = 'free'): array
    {
        $user = User::factory()->plan($plan)->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    // ── Free-tier feature gates ───────────────────────────────────────────

    public function test_free_couple_is_redirected_from_seating_to_upgrade(): void
    {
        [$user] = $this->coupleOwner('free');

        $this->actingAs($user)->get('/seating')->assertRedirect(route('plan.edit'));
    }

    public function test_premium_couple_can_open_seating(): void
    {
        [$user] = $this->coupleOwner('premium');

        $this->actingAs($user)->get('/seating')->assertOk();
    }

    public function test_free_couple_cannot_publish_their_website(): void
    {
        [$user, $wedding] = $this->coupleOwner('free');

        $this->actingAs($user)->put('/website', ['is_published' => true])->assertRedirect();

        $this->assertFalse((bool) $wedding->website()->first()->is_published);
    }

    public function test_premium_couple_can_publish_their_website(): void
    {
        [$user, $wedding] = $this->coupleOwner('premium');

        $this->actingAs($user)->put('/website', ['is_published' => true])->assertRedirect();

        $this->assertTrue((bool) $wedding->website()->first()->is_published);
    }

    // ── Admin free-tier feature unlocks ───────────────────────────────────

    public function test_admin_can_unlock_a_premium_feature_for_the_free_tier(): void
    {
        [$user] = $this->coupleOwner('free');

        // Locked by default.
        $this->actingAs($user)->get('/seating')->assertRedirect(route('plan.edit'));

        // Admin unlocks seating for everyone on the free tier.
        PlanFeatures::save(['seating' => true]);

        $this->actingAs($user)->get('/seating')->assertOk();
    }

    public function test_relocking_a_feature_removes_free_access(): void
    {
        [$user] = $this->coupleOwner('free');

        PlanFeatures::save(['seating' => true]);
        $this->actingAs($user)->get('/seating')->assertOk();

        PlanFeatures::save(['seating' => false]);
        $this->actingAs($user)->get('/seating')->assertRedirect(route('plan.edit'));
    }

    public function test_unlocking_one_feature_does_not_unlock_others(): void
    {
        [$user, $wedding] = $this->coupleOwner('free');

        PlanFeatures::save(['seating' => true]);

        // website_publish stays gated.
        $this->actingAs($user)->put('/website', ['is_published' => true])->assertRedirect();
        $this->assertFalse((bool) $wedding->website()->first()->is_published);
    }

    public function test_admin_can_view_and_save_free_tier_features(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)->get('/admin/features')->assertOk()
            ->assertInertia(fn ($page) => $page->component('admin/features')->has('features', 9));

        $this->actingAs($admin)->put('/admin/features', [
            'features' => ['registry' => true, 'seating' => false],
        ])->assertRedirect();

        $this->assertTrue(PlanFeatures::freeTierGrants('registry'));
        $this->assertFalse(PlanFeatures::freeTierGrants('seating'));
    }

    public function test_non_admin_cannot_manage_features(): void
    {
        $user = User::factory()->create(['account_type' => 'couple']);

        $this->actingAs($user)->get('/admin/features')->assertForbidden();
        $this->actingAs($user)->put('/admin/features', ['features' => ['seating' => true]])->assertForbidden();
    }

    // ── Plan page is segmented by account type ────────────────────────────

    public function test_couple_sees_only_couple_tiers(): void
    {
        $user = User::factory()->create(['account_type' => 'couple']);

        $this->actingAs($user)
            ->get('/settings/plan')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('account_type', 'couple')
                ->has('tiers', 2)
                ->where('tiers.0.key', 'free')
                ->where('tiers.1.key', 'premium')
            );
    }

    public function test_planner_sees_only_the_planner_tier(): void
    {
        $user = User::factory()->create(['account_type' => 'planner']);

        $this->actingAs($user)
            ->get('/settings/plan')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('account_type', 'planner')
                ->has('tiers', 1)
                ->where('tiers.0.key', 'planner')
            );
    }

    public function test_vendor_sees_no_subscription_tiers(): void
    {
        $user = User::factory()->create(['account_type' => 'vendor']);

        $this->actingAs($user)
            ->get('/settings/plan')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('account_type', 'vendor')
                ->has('tiers', 0)
            );
    }
}
