<?php

namespace Database\Seeders;

use App\Enums\VendorCategory;
use App\Models\LocalContent;
use App\Support\Seo\LocalGuide;
use Illuminate\Database\Seeder;

/**
 * Seeds genuine, data-backed local-guide copy + FAQs for the focused SEO
 * tranche: every Ontario category hub, plus the top metros × highest-intent
 * categories. Deterministic ({@see LocalGuide}) so it needs no API key and is
 * fully reproducible; idempotent so re-running only refreshes copy. Anything
 * outside this tranche stays noindex until it earns vendors or content, which
 * keeps us safely inside Google's thin-content rules.
 *
 *   php artisan db:seed --class=Database\\Seeders\\LocalContentSeeder
 */
class LocalContentSeeder extends Seeder
{
    /** Top metros by wedding-search demand — the city pages we index first. */
    private const METROS = [
        'toronto', 'ottawa', 'mississauga', 'hamilton',
        'london', 'niagara', 'kitchener-waterloo', 'barrie',
    ];

    /** Highest-intent categories to pair with each metro. */
    private const CATEGORIES = [
        VendorCategory::Photography,
        VendorCategory::Venue,
        VendorCategory::Planner,
        VendorCategory::Florist,
        VendorCategory::Catering,
    ];

    public function run(LocalGuide $guide): void
    {
        $written = 0;

        // Ontario category hubs — one per SEO category.
        foreach (VendorCategory::seoCases() as $cat) {
            if ($content = $guide->hub($cat)) {
                $this->store($cat->value, null, $content);
                $written++;
            }
        }

        // Metro × category city pages.
        foreach (self::CATEGORIES as $cat) {
            foreach (self::METROS as $citySlug) {
                if ($content = $guide->city($cat, $citySlug)) {
                    $this->store($cat->value, $citySlug, $content);
                    $written++;
                }
            }
        }

        $this->command?->info("LocalContentSeeder: {$written} local pages seeded with guides + FAQs.");
    }

    /** @param array{intro:string, faqs:array} $content */
    private function store(string $category, ?string $citySlug, array $content): void
    {
        LocalContent::updateOrCreate(
            ['category' => $category, 'city_slug' => $citySlug],
            ['intro' => $content['intro'], 'faqs' => $content['faqs']],
        );
    }
}
