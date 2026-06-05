<?php

namespace Database\Seeders;

use App\Enums\AgeGroup;
use App\Enums\GuestSide;
use App\Enums\Role;
use App\Enums\RsvpStatus;
use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\User;
use App\Models\Wedding;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with a demo admin, couple, and wedding.
     */
    public function run(): void
    {
        // Platform administrator.
        User::factory()->admin()->create([
            'name' => 'Atelier Admin',
            'email' => 'admin@wedflow.test',
        ]);

        // The couple.
        $owner = User::factory()->plan('planner')->create([
            'name' => 'Amelia Hart',
            'email' => 'couple@wedflow.test',
        ]);

        $partner = User::factory()->create([
            'name' => 'Julian Reyes',
            'email' => 'partner@wedflow.test',
        ]);

        $wedding = Wedding::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Amelia & Julian',
            'slug' => 'amelia-and-julian',
            'event_date' => now()->addMonths(8)->toDateString(),
        ]);

        // Memberships.
        $wedding->members()->attach($owner->id, [
            'role' => Role::Owner->value,
            'accepted_at' => now(),
        ]);
        $wedding->members()->attach($partner->id, [
            'role' => Role::Partner->value,
            'accepted_at' => now(),
        ]);

        $owner->forceFill(['current_wedding_id' => $wedding->id])->save();
        $partner->forceFill(['current_wedding_id' => $wedding->id])->save();

        $this->seedGuests($wedding);
    }

    /** A small, realistic guest list so the demo workspace feels alive. */
    protected function seedGuests(Wedding $wedding): void
    {
        $hart = GuestGroup::create(['wedding_id' => $wedding->id, 'name' => 'The Hart Family']);
        $reyes = GuestGroup::create(['wedding_id' => $wedding->id, 'name' => 'The Reyes Family']);

        $people = [
            ['Eleanor', 'Hart', $hart->id, GuestSide::PartnerOne, RsvpStatus::Attending],
            ['Thomas', 'Hart', $hart->id, GuestSide::PartnerOne, RsvpStatus::Attending],
            ['Sofia', 'Reyes', $reyes->id, GuestSide::PartnerTwo, RsvpStatus::Attending],
            ['Mateo', 'Reyes', $reyes->id, GuestSide::PartnerTwo, RsvpStatus::Pending],
            ['Grace', 'Lin', null, GuestSide::Both, RsvpStatus::Maybe],
            ['Daniel', 'Okafor', null, GuestSide::PartnerOne, RsvpStatus::Declined],
        ];

        foreach ($people as [$first, $last, $groupId, $side, $status]) {
            Guest::create([
                'wedding_id' => $wedding->id,
                'group_id' => $groupId,
                'first_name' => $first,
                'last_name' => $last,
                'side' => $side,
                'age_group' => AgeGroup::Adult,
                'rsvp_status' => $status,
            ]);
        }
    }
}
