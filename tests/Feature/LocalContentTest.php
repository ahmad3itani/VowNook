<?php

namespace Tests\Feature;

use App\Enums\VendorCategory;
use App\Models\LocalContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LocalContentTest extends TestCase
{
    use RefreshDatabase;

    private function enableAi(): void
    {
        config([
            'ai.enabled' => true,
            'ai.provider' => 'anthropic',
            'ai.anthropic.key' => 'test-key',
            'ai.anthropic.base_url' => 'https://api.anthropic.com',
            'ai.anthropic.version' => '2023-06-01',
            'ai.openrouter.key' => null,
            'ai.model' => 'claude-sonnet-4-6',
            'ai.local_seo.enabled' => true,
            'ai.local_seo.per_run' => 50,
        ]);
    }

    private function fakeGuide(): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response([
            'content' => [['type' => 'tool_use', 'name' => 'local_guide', 'input' => [
                'intro_markdown' => 'A genuinely useful intro about local vendors and typical CAD price ranges.',
                'faqs' => [
                    ['question' => 'How much does it cost?', 'answer' => 'Typically a few thousand dollars, depending on scope.'],
                    ['question' => 'When should I book?', 'answer' => 'Around 12 months ahead for popular dates.'],
                ],
            ]]],
            'stop_reason' => 'tool_use',
        ])]);
    }

    public function test_it_fills_the_category_hubs(): void
    {
        $this->enableAi();
        $this->fakeGuide();

        $this->artisan('seo:generate-local')->assertSuccessful();

        // No vendors exist, so only the always-eligible Ontario hubs are filled.
        $hubs = count(VendorCategory::seoCases());
        $this->assertSame($hubs, LocalContent::whereNull('city_slug')->count());

        $cat = VendorCategory::seoCases()[0];
        $this->assertDatabaseHas('local_contents', ['category' => $cat->value, 'city_slug' => null]);
    }

    public function test_it_dedupes_already_filled_pages(): void
    {
        $this->enableAi();
        $this->fakeGuide();

        $cat = VendorCategory::seoCases()[0];
        LocalContent::create(['category' => $cat->value, 'city_slug' => null, 'intro' => 'Existing copy.', 'faqs' => []]);

        // Re-running must not duplicate the existing page (unique constraint would
        // otherwise throw) and must still fill the remaining hubs.
        $this->artisan('seo:generate-local')->assertSuccessful();

        $this->assertSame(1, LocalContent::where('category', $cat->value)->whereNull('city_slug')->count());
        $this->assertSame('Existing copy.', LocalContent::forPage($cat->value, null)->intro);
        $this->assertSame(count(VendorCategory::seoCases()), LocalContent::count());
    }

    public function test_it_skips_when_disabled(): void
    {
        $this->enableAi();
        config(['ai.local_seo.enabled' => false]);
        Http::fake();

        $this->artisan('seo:generate-local')->assertSuccessful();

        $this->assertSame(0, LocalContent::count());
        Http::assertNothingSent();
    }

    public function test_it_degrades_when_ai_not_configured(): void
    {
        config([
            'ai.anthropic.key' => null,
            'ai.openrouter.key' => null,
            'ai.local_seo.enabled' => true,
        ]);
        Http::fake();

        $this->artisan('seo:generate-local')->assertSuccessful();

        $this->assertSame(0, LocalContent::count());
        Http::assertNothingSent();
    }

    public function test_the_hub_page_renders_the_guide_and_faq_schema(): void
    {
        $cat = VendorCategory::seoCases()[0];
        LocalContent::create([
            'category' => $cat->value,
            'city_slug' => null,
            'intro' => 'Unique local guide copy for this category in Ontario.',
            'faqs' => [['question' => 'Is this a real question?', 'answer' => 'Yes it is.']],
        ]);

        $this->get(route('local.category', $cat->seoSlug()))
            ->assertOk()
            ->assertSee('Unique local guide copy for this category in Ontario.')
            ->assertSee('FAQPage')
            ->assertSee('Is this a real question?');
    }
}
