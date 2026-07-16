<?php

namespace Tests\Feature;

use App\Models\BudgetCategory;
use App\Models\BudgetItem;
use App\Models\User;
use App\Models\Wedding;
use App\Support\Budget\BudgetAllocator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Bring your budget, we'll make it work" — capturing the total + city, showing
 * the allocation, and seeding the budget tracker from it (idempotently).
 */
class BudgetFirstTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Wedding} */
    protected function coupleWithWedding(): array
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_store_saves_a_band_budget_and_city(): void
    {
        [$user, $wedding] = $this->coupleWithWedding();

        $this->actingAs($user)
            ->post('/budget/plan', ['band' => '25-40k', 'city' => 'toronto'])
            ->assertRedirect();

        $wedding->refresh();
        $this->assertSame(3_200_000, $wedding->total_budget_cents);
        $this->assertSame('toronto', $wedding->city);
    }

    public function test_store_prefers_an_exact_amount_over_a_band(): void
    {
        [$user, $wedding] = $this->coupleWithWedding();

        $this->actingAs($user)
            ->post('/budget/plan', ['band' => '25-40k', 'exact_dollars' => 28000, 'city' => 'ottawa'])
            ->assertRedirect();

        $this->assertSame(2_800_000, $wedding->refresh()->total_budget_cents);
    }

    public function test_show_returns_the_allocation_once_a_budget_is_set(): void
    {
        [$user, $wedding] = $this->coupleWithWedding();
        $wedding->update(['total_budget_cents' => 3_000_000, 'city' => 'hamilton']);

        $this->actingAs($user)
            ->get('/budget/plan')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('budget/plan')
                ->where('wedding.total_budget_cents', 3_000_000)
                ->has('allocation', count(BudgetAllocator::SPLIT))
                ->where('realism.verdict', 'unknown') // no guests entered yet
            );
    }

    public function test_apply_seeds_the_budget_tracker_idempotently(): void
    {
        [$user, $wedding] = $this->coupleWithWedding();
        $wedding->update(['total_budget_cents' => 3_000_000, 'city' => 'toronto']);

        $this->actingAs($user)->post('/budget/plan/apply')->assertRedirect('/budget');
        $this->actingAs($user)->post('/budget/plan/apply')->assertRedirect('/budget'); // re-apply is a no-op

        $splitCount = count(BudgetAllocator::SPLIT);
        $this->assertSame($splitCount, BudgetCategory::where('wedding_id', $wedding->id)->count());
        $this->assertSame($splitCount, BudgetItem::where('wedding_id', $wedding->id)->where('name', 'Estimated budget')->count());
        $this->assertSame(3_000_000, (int) BudgetItem::where('wedding_id', $wedding->id)->sum('estimated_cents'));
    }
}
