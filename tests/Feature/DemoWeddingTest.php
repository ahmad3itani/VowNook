<?php

namespace Tests\Feature;

use App\Models\GuestbookEntry;
use App\Models\TimelineEvent;
use App\Models\Wedding;
use App\Models\WeddingPartyMember;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The homepage links to /w/amelia-and-julian ("See a live wedding site"). That
 * data lives only in DatabaseSeeder (local), so production 404s until
 * `demo:wedding` is run. This guards that the command builds a working,
 * published public site and is safe to re-run.
 */
class DemoWeddingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_published_public_wedding_site(): void
    {
        $this->artisan('demo:wedding')->assertSuccessful();

        $wedding = Wedding::where('slug', 'amelia-and-julian')->first();
        $this->assertNotNull($wedding);
        $this->assertTrue((bool) $wedding->website?->is_published);

        // The homepage link now resolves to a published site (was a 404 in prod).
        $this->get('/w/amelia-and-julian')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/website')
                ->where('published', true)
                ->where('wedding.name', 'Amelia & Julian')
            );
    }

    public function test_it_is_idempotent(): void
    {
        $this->artisan('demo:wedding')->assertSuccessful();
        $this->artisan('demo:wedding')->assertSuccessful();

        $this->assertSame(1, Wedding::where('slug', 'amelia-and-julian')->count());

        $wedding = Wedding::where('slug', 'amelia-and-julian')->first();
        $this->assertSame(4, TimelineEvent::where('wedding_id', $wedding->id)->count());
        $this->assertSame(4, WeddingPartyMember::where('wedding_id', $wedding->id)->count());
        $this->assertSame(1, GuestbookEntry::where('wedding_id', $wedding->id)->count());
    }

    public function test_purge_removes_it(): void
    {
        $this->artisan('demo:wedding')->assertSuccessful();
        $this->artisan('demo:wedding', ['--purge' => true])->assertSuccessful();

        $this->assertNull(Wedding::where('slug', 'amelia-and-julian')->first());
    }
}
