<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\BudgetCategory;
use App\Models\BudgetItem;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    protected function ownerWithWedding(): array
    {
        $user = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_index_is_scoped_to_the_active_wedding(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        BudgetItem::factory()->count(2)->create(['wedding_id' => $wedding->id]);
        BudgetItem::factory()->count(3)->create();

        $this->actingAs($user)
            ->get('/budget')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('budget/index')
                ->has('items', 2)
            );
    }

    public function test_member_can_create_an_item_with_dollar_amounts(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();

        $this->actingAs($user)->post('/budget', [
            'name' => 'Venue deposit',
            'estimated_amount' => 1200.50,
            'paid_amount' => 300,
        ])->assertRedirect();

        $this->assertDatabaseHas('budget_items', [
            'wedding_id' => $wedding->id,
            'name' => 'Venue deposit',
            'estimated_cents' => 120050,
            'paid_cents' => 30000,
        ]);
    }

    public function test_viewer_cannot_create_an_item(): void
    {
        $owner = User::factory()->create();
        $wedding = Wedding::factory()->create(['owner_id' => $owner->id]);

        $viewer = User::factory()->create();
        $wedding->members()->attach($viewer->id, ['role' => Role::Viewer->value]);
        $viewer->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->actingAs($viewer)->post('/budget', [
            'name' => 'Nope',
            'estimated_amount' => 10,
            'paid_amount' => 0,
        ])->assertForbidden();
    }

    public function test_cannot_update_an_item_from_another_wedding(): void
    {
        [$user] = $this->ownerWithWedding();
        $foreign = BudgetItem::factory()->create();

        $this->actingAs($user)->put("/budget/{$foreign->id}", [
            'name' => 'Hijack',
            'estimated_amount' => 1,
            'paid_amount' => 0,
        ])->assertNotFound();
    }

    public function test_stats_are_computed_in_dollars(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        BudgetItem::factory()->create([
            'wedding_id' => $wedding->id,
            'estimated_cents' => 100000,
            'actual_cents' => 120000,
            'paid_cents' => 50000,
        ]);
        BudgetItem::factory()->create([
            'wedding_id' => $wedding->id,
            'estimated_cents' => 50000,
            'actual_cents' => null,
            'paid_cents' => 0,
        ]);

        $this->actingAs($user)
            ->get('/budget')
            ->assertInertia(fn ($page) => $page
                ->where('stats.estimated', 1500)
                ->where('stats.projected', 1700) // 1200 actual + 500 estimated fallback
                ->where('stats.paid', 500)
                ->where('stats.outstanding', 1200)
            );
    }

    public function test_category_must_belong_to_the_active_wedding(): void
    {
        [$user] = $this->ownerWithWedding();
        $foreignCategory = BudgetCategory::factory()->create();

        $this->actingAs($user)->post('/budget', [
            'name' => 'Cake',
            'estimated_amount' => 200,
            'paid_amount' => 0,
            'category_id' => $foreignCategory->id,
        ])->assertSessionHasErrors('category_id');
    }

    public function test_member_can_delete_an_item(): void
    {
        [$user, $wedding] = $this->ownerWithWedding();
        $item = BudgetItem::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)
            ->delete("/budget/{$item->id}")
            ->assertRedirect();

        $this->assertDatabaseMissing('budget_items', ['id' => $item->id]);
    }
}
