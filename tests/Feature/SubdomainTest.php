<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingWebsite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubdomainTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.root_domain' => 'vownook.com']);
    }

    /** @return array{0: User, 1: Wedding} */
    private function premiumCouple(): array
    {
        $user = User::factory()->plan('premium')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_free_couple_cannot_claim_a_subdomain(): void
    {
        $user = User::factory()->plan('free')->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        // Mutations on a gated feature return 403 (GET would redirect to the plan page).
        $this->actingAs($user)->put('/website/subdomain', ['subdomain' => 'amelia-and-julian'])
            ->assertForbidden();
    }

    public function test_couple_can_claim_a_subdomain(): void
    {
        [$user, $wedding] = $this->premiumCouple();

        $this->actingAs($user)->put('/website/subdomain', ['subdomain' => 'amelia-and-julian'])->assertRedirect();

        $this->assertDatabaseHas('wedding_websites', [
            'wedding_id' => $wedding->id, 'subdomain' => 'amelia-and-julian',
        ]);
    }

    public function test_reserved_and_taken_subdomains_are_rejected(): void
    {
        [$user] = $this->premiumCouple();

        $this->actingAs($user)->put('/website/subdomain', ['subdomain' => 'www'])
            ->assertSessionHasErrors('subdomain');

        // Already taken by another wedding.
        $other = Wedding::factory()->create();
        WeddingWebsite::factory()->create(['wedding_id' => $other->id, 'subdomain' => 'taken-one']);

        $this->actingAs($user)->put('/website/subdomain', ['subdomain' => 'taken-one'])
            ->assertSessionHasErrors('subdomain');

        $this->actingAs($user)->put('/website/subdomain', ['subdomain' => 'UPPER CASE!'])
            ->assertSessionHasErrors('subdomain');
    }

    public function test_availability_check_endpoint(): void
    {
        [$user] = $this->premiumCouple();

        $this->actingAs($user)->getJson('/website/subdomain/check?value=brand-new')
            ->assertOk()->assertJson(['available' => true]);

        $this->actingAs($user)->getJson('/website/subdomain/check?value=admin')
            ->assertOk()->assertJson(['available' => false, 'reason' => 'reserved']);

        $this->actingAs($user)->getJson('/website/subdomain/check?value=ab')
            ->assertOk()->assertJson(['available' => false, 'reason' => 'invalid']);
    }

    public function test_subdomain_host_resolves_a_published_wedding_site(): void
    {
        $wedding = Wedding::factory()->create();
        WeddingWebsite::factory()->create([
            'wedding_id' => $wedding->id, 'subdomain' => 'amelia-and-julian', 'is_published' => true,
        ]);

        $this->get('http://amelia-and-julian.vownook.com/')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/website')
                ->where('wedding.slug', $wedding->slug)
                ->where('published', true)
            );
    }

    public function test_unpublished_or_unknown_subdomain_redirects_to_the_main_site(): void
    {
        $wedding = Wedding::factory()->create();
        WeddingWebsite::factory()->create([
            'wedding_id' => $wedding->id, 'subdomain' => 'draft-site', 'is_published' => false,
        ]);

        $this->get('http://draft-site.vownook.com/')->assertRedirect(config('app.url'));
        $this->get('http://nobody.vownook.com/')->assertRedirect(config('app.url'));
    }

    public function test_apex_home_still_renders_normally(): void
    {
        $this->get('http://vownook.com/')->assertOk();
    }
}
