<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * GEO / AEO: the structured data + content that lets AI assistants (ChatGPT,
 * Perplexity, Gemini, Google AI Overviews) confidently describe and recommend
 * the business. All server-rendered, so it reaches AI crawlers even with SSR off.
 */
class GeoSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_organization_schema_carries_entity_signals(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('"@type":"Organization"', false)
            ->assertSee('knowsAbout', false)
            ->assertSee('Honeymoon planning', false)
            ->assertSee('contactPoint', false);
    }

    public function test_how_it_works_emits_faqpage_schema(): void
    {
        $this->get(route('how-it-works'))
            ->assertOk()
            ->assertSee('"@type":"FAQPage"', false)
            ->assertSee('What is VowNook?', false)
            ->assertSee('How does VowNook make money?', false);
    }

    public function test_llms_txt_describes_the_business(): void
    {
        $this->get('/llms.txt')
            ->assertOk()
            ->assertSee('Honest reviews')
            ->assertSee('Atelier');
    }
}
