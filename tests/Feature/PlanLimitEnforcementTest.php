<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Guest;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PlanLimitEnforcementTest extends TestCase
{
    use RefreshDatabase;

    /** @param array<string,mixed> $overrides */
    protected function ownerWithWedding(string $plan = 'free', array $overrides = []): array
    {
        config($overrides);

        $user = User::factory()->plan($plan)->create();
        $wedding = Wedding::factory()->create(['owner_id' => $user->id]);
        $wedding->members()->attach($user->id, ['role' => Role::Owner->value, 'accepted_at' => now()]);
        $user->forceFill(['current_wedding_id' => $wedding->id])->save();

        return [$user, $wedding];
    }

    public function test_guest_cap_blocks_creation_past_the_limit(): void
    {
        [$user, $wedding] = $this->ownerWithWedding('free', [
            'plans.tiers.free.max_guests_per_wedding' => 1,
        ]);
        Guest::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)->post('/guests', [
            'first_name' => 'Over',
            'last_name' => 'Limit',
            'side' => 'both',
            'age_group' => 'adult',
            'rsvp_status' => 'pending',
        ])->assertSessionHasErrors('first_name');

        $this->assertSame(1, Guest::query()->forWedding($wedding->id)->count());
    }

    public function test_unlimited_plan_allows_creation(): void
    {
        [$user, $wedding] = $this->ownerWithWedding('planner', [
            'plans.tiers.free.max_guests_per_wedding' => 1,
        ]);
        Guest::factory()->create(['wedding_id' => $wedding->id]);

        $this->actingAs($user)->post('/guests', [
            'first_name' => 'Fine',
            'last_name' => 'ToAdd',
            'side' => 'both',
            'age_group' => 'adult',
            'rsvp_status' => 'pending',
        ])->assertRedirect();

        $this->assertSame(2, Guest::query()->forWedding($wedding->id)->count());
    }

    public function test_gallery_cap_blocks_upload_past_the_limit(): void
    {
        Storage::fake();
        [$user] = $this->ownerWithWedding('free', [
            'plans.tiers.free.max_gallery_photos' => 1,
        ]);

        $this->actingAs($user)->post('/gallery', [
            'photo' => UploadedFile::fake()->image('one.jpg'),
        ])->assertRedirect();

        $this->actingAs($user)->post('/gallery', [
            'photo' => UploadedFile::fake()->image('two.jpg'),
        ])->assertSessionHasErrors('photo');
    }

    public function test_collaborator_cap_blocks_invites_past_the_limit(): void
    {
        [$owner, $wedding] = $this->ownerWithWedding('free', [
            'plans.tiers.free.max_collaborators_per_wedding' => 1,
        ]);

        $first = User::factory()->create();
        $wedding->members()->attach($first->id, ['role' => Role::Viewer->value, 'accepted_at' => now()]);

        User::factory()->create(['email' => 'second@example.com']);

        $this->actingAs($owner)->post('/collaborators', [
            'email' => 'second@example.com',
            'role' => 'viewer',
        ])->assertSessionHasErrors('email');

        $this->assertSame(1, $wedding->members()->where('users.id', '!=', $owner->id)->count());
    }
}
