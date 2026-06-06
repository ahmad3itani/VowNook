<?php

namespace Tests\Feature;

use App\Models\Wedding;
use App\Models\WeddingWebsite;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicWebsiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_url_renders_the_public_website(): void
    {
        $wedding = Wedding::factory()->create();

        $this->get("/w/{$wedding->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/website')
                ->where('wedding.slug', $wedding->slug)
                ->where('published', false)
                ->where('content', null)
            );
    }

    public function test_published_content_is_exposed(): void
    {
        $wedding = Wedding::factory()->create();
        WeddingWebsite::factory()->create([
            'wedding_id' => $wedding->id,
            'is_published' => true,
            'venue_name' => 'Rosewood Estate',
        ]);

        $this->get("/w/{$wedding->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('published', true)
                ->where('content.venue_name', 'Rosewood Estate')
            );
    }

    public function test_unpublished_content_is_hidden(): void
    {
        $wedding = Wedding::factory()->create();
        WeddingWebsite::factory()->create([
            'wedding_id' => $wedding->id,
            'is_published' => false,
            'venue_name' => 'Secret Venue',
        ]);

        $this->get("/w/{$wedding->slug}")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('published', false)
                ->where('content', null)
            );
    }
}
