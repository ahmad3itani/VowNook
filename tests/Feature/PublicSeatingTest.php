<?php

namespace Tests\Feature;

use App\Models\Guest;
use App\Models\SeatingTable;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicSeatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_renders_the_public_seat_finder(): void
    {
        $wedding = Wedding::factory()->create();

        $this->get("/w/{$wedding->slug}/seats")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/seats')
                ->where('searched', false)
                ->has('matches', 0)
                ->where('wedding.slug', $wedding->slug)
            );
    }

    public function test_find_returns_the_guests_table_and_tablemates(): void
    {
        $wedding = Wedding::factory()->create();
        $table = SeatingTable::factory()->create(['wedding_id' => $wedding->id, 'name' => 'Table 7']);

        Guest::factory()->create([
            'wedding_id' => $wedding->id,
            'first_name' => 'Amelia',
            'last_name' => 'Stone',
            'table_id' => $table->id,
        ]);
        Guest::factory()->create([
            'wedding_id' => $wedding->id,
            'first_name' => 'Noah',
            'last_name' => 'Brooks',
            'table_id' => $table->id,
        ]);

        $this->get("/w/{$wedding->slug}/seats?name=Amelia")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('public/seats')
                ->where('searched', true)
                ->has('matches', 1)
                ->where('matches.0.name', 'Amelia Stone')
                ->where('matches.0.table', 'Table 7')
                ->where('matches.0.tablemates', ['Noah Brooks'])
            );
    }

    public function test_find_ignores_unseated_guests(): void
    {
        $wedding = Wedding::factory()->create();
        Guest::factory()->create([
            'wedding_id' => $wedding->id,
            'first_name' => 'Unseated',
            'table_id' => null,
        ]);

        $this->get("/w/{$wedding->slug}/seats?name=Unseated")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('searched', true)
                ->has('matches', 0)
            );
    }

    public function test_find_is_scoped_to_the_wedding(): void
    {
        $wedding = Wedding::factory()->create();
        $other = Wedding::factory()->create();
        $table = SeatingTable::factory()->create(['wedding_id' => $other->id]);
        Guest::factory()->create([
            'wedding_id' => $other->id,
            'first_name' => 'Amelia',
            'table_id' => $table->id,
        ]);

        $this->get("/w/{$wedding->slug}/seats?name=Amelia")
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('matches', 0));
    }

    public function test_a_short_query_does_not_search(): void
    {
        $wedding = Wedding::factory()->create();

        $this->get("/w/{$wedding->slug}/seats?name=A")
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('searched', false)
                ->has('matches', 0)
            );
    }
}
