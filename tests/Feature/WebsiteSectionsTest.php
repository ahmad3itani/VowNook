<?php

namespace Tests\Feature;

use App\Models\GuestbookEntry;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingWebsite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class WebsiteSectionsTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Wedding} */
    private function couple(string $plan = 'premium'): array
    {
        $user = User::factory()->plan($plan)->create(['account_type' => 'couple']);
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_couple_can_add_a_wedding_party_member(): void
    {
        [$user, $wedding] = $this->couple();

        $this->actingAs($user)->post('/website/party', [
            'name' => 'Sophie Tremblay',
            'role' => 'Maid of Honour',
            'side' => 'partner_a',
            'bio' => 'Best friend since grade school.',
        ])->assertRedirect();

        $this->assertDatabaseHas('wedding_party_members', [
            'wedding_id' => $wedding->id,
            'name' => 'Sophie Tremblay',
            'side' => 'partner_a',
        ]);
    }

    public function test_couple_can_save_faq_and_local_guide(): void
    {
        [$user, $wedding] = $this->couple();

        $this->actingAs($user)->put('/website', [
            'faq_items' => [['question' => 'What should I wear?', 'answer' => 'Cocktail attire.']],
            'local_recommendations' => [[
                'title' => 'Old Port', 'category' => 'Attraction',
                'description' => 'Historic waterfront.', 'url' => 'https://example.com',
            ]],
        ])->assertRedirect();

        $website = $wedding->website()->first();
        $this->assertSame('What should I wear?', $website->faq_items[0]['question']);
        $this->assertSame('Old Port', $website->local_recommendations[0]['title']);
    }

    public function test_guestbook_entry_is_held_until_the_couple_approves_it(): void
    {
        [$user, $wedding] = $this->couple();
        WeddingWebsite::create(['wedding_id' => $wedding->id, 'is_published' => true]);

        $this->post("/w/{$wedding->slug}/guestbook", [
            'name' => 'Aunt May',
            'message' => 'So happy for you both!',
        ])->assertRedirect();

        $entry = GuestbookEntry::firstWhere('wedding_id', $wedding->id);
        $this->assertFalse($entry->isApproved());

        // Not shown publicly until approved.
        $this->get("/w/{$wedding->slug}")->assertInertia(fn (Assert $page) => $page->has('guestbook', 0));

        $this->actingAs($user)->post("/website/guestbook/{$entry->id}/approve")->assertRedirect();

        $this->assertTrue($entry->fresh()->isApproved());
        $this->get("/w/{$wedding->slug}")->assertInertia(fn (Assert $page) => $page->has('guestbook', 1));
    }

    public function test_ai_fill_is_blocked_for_free_couples(): void
    {
        [$user] = $this->couple('free');

        $this->actingAs($user)->postJson('/website/ai-fill', ['section' => 'welcome'])->assertForbidden();
    }

    public function test_ai_fill_drafts_copy_for_subscribers(): void
    {
        config(['ai.enabled' => true, 'ai.anthropic.key' => 'test-key', 'ai.openrouter.key' => null]);
        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [[
                'type' => 'tool_use', 'name' => 'write_copy',
                'input' => ['content' => 'Welcome to our celebration!'],
            ]],
            'stop_reason' => 'tool_use',
        ], 200)]);

        [$user] = $this->couple('premium');

        $this->actingAs($user)->postJson('/website/ai-fill', ['section' => 'welcome'])
            ->assertOk()
            ->assertJson(['available' => true, 'content' => 'Welcome to our celebration!']);
    }
}
