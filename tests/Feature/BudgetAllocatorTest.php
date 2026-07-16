<?php

namespace Tests\Feature;

use App\Support\Budget\BudgetAllocator;
use Tests\TestCase;

/**
 * The deterministic budget engine behind "bring your budget, we'll make it
 * work" — the split must total the budget, the city index must bend the
 * realism check, and guest count gates the verdict.
 */
class BudgetAllocatorTest extends TestCase
{
    private BudgetAllocator $allocator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->allocator = new BudgetAllocator();
    }

    public function test_allocation_splits_the_full_total_across_categories(): void
    {
        $rows = $this->allocator->allocate(3_000_000); // $30,000

        $this->assertCount(count(BudgetAllocator::SPLIT), $rows);
        $this->assertSame(3_000_000, array_sum(array_column($rows, 'amount_cents')));

        $venue = collect($rows)->firstWhere('label', 'Venue');
        $this->assertSame(0.40, $venue['percent']);
        $this->assertSame(1_200_000, $venue['amount_cents']);
        $this->assertContains('venue', $venue['vendor_categories']);
    }

    public function test_city_index_defaults_to_one_for_unknown_or_null(): void
    {
        $this->assertSame(1.20, $this->allocator->cityIndex('toronto'));
        $this->assertSame(1.0, $this->allocator->cityIndex('nowhere-town'));
        $this->assertSame(1.0, $this->allocator->cityIndex(null));
    }

    public function test_realism_verdict_tracks_budget_versus_typical(): void
    {
        // hamilton index = 1.0; 100 guests → typical = $8k fixed + $220*100 = $30,000.
        $this->assertSame('tight', $this->allocator->realism(1_500_000, 'hamilton', 100)['verdict']);
        $this->assertSame('comfortable', $this->allocator->realism(3_000_000, 'hamilton', 100)['verdict']);
        $this->assertSame('generous', $this->allocator->realism(5_000_000, 'hamilton', 100)['verdict']);
    }

    public function test_a_pricier_city_makes_the_same_budget_tighter(): void
    {
        $hamilton = $this->allocator->realism(3_000_000, 'hamilton', 100);
        $toronto = $this->allocator->realism(3_000_000, 'toronto', 100);

        $this->assertGreaterThan($hamilton['typical_cents'], $toronto['typical_cents']);
        $this->assertSame('comfortable', $hamilton['verdict']);
        // $30k against Toronto's ~$36k typical is a tighter (but still comfortable) fit.
        $this->assertLessThan($hamilton['ratio'], $toronto['ratio']);
    }

    public function test_realism_is_unknown_without_a_guest_count(): void
    {
        $this->assertSame('unknown', $this->allocator->realism(3_000_000, 'toronto', 0)['verdict']);
    }

    public function test_bands_are_offered_for_capture(): void
    {
        $bands = BudgetAllocator::bands();
        $this->assertNotEmpty($bands);
        $this->assertSame('under-15k', $bands[0]['key']);
        $this->assertTrue(collect($bands)->every(fn ($b) => $b['cents'] > 0));
    }

    public function test_category_budgets_maps_each_vendor_category_to_its_bucket_amount(): void
    {
        $budgets = $this->allocator->categoryBudgetsFor(3_000_000); // $30,000

        // Venue is its own bucket: 40% of $30,000.
        $this->assertSame(1_200_000, $budgets['venue']);

        // Attire and beauty share the "Attire & beauty" bucket (6%) — both get
        // the same cap, it isn't split further between them.
        $attireBeauty = (int) round(3_000_000 * 0.06);
        $this->assertSame($attireBeauty, $budgets['attire']);
        $this->assertSame($attireBeauty, $budgets['beauty']);
    }

    public function test_category_budgets_covers_every_vendor_category_slug_in_split(): void
    {
        $budgets = $this->allocator->categoryBudgetsFor(3_000_000);

        $expectedSlugs = collect(BudgetAllocator::SPLIT)->flatMap(fn ($row) => $row[1])->all();

        foreach ($expectedSlugs as $slug) {
            $this->assertArrayHasKey($slug, $budgets);
        }
    }
}
