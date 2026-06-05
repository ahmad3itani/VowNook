<?php

namespace Database\Seeders;

use App\Enums\Role;
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
    }
}
