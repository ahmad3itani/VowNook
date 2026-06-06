<?php

namespace Database\Seeders;

use App\Enums\AgeGroup;
use App\Enums\GuestSide;
use App\Enums\Role;
use App\Enums\RsvpStatus;
use App\Models\BudgetCategory;
use App\Models\BudgetItem;
use App\Enums\TaskCategory;
use App\Enums\TaskPriority;
use App\Enums\VendorCategory;
use App\Enums\VendorStatus;
use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\Task;
use App\Models\User;
use App\Models\Vendor;
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
        $this->seedBudget($wedding);
        $this->seedVendors($wedding);
        $this->seedChecklist($wedding, $owner);
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

    /** A starter budget so the demo workspace shows meaningful totals. */
    protected function seedBudget(Wedding $wedding): void
    {
        $categories = collect(['Venue', 'Catering', 'Attire', 'Flowers', 'Photography'])
            ->mapWithKeys(fn (string $name, int $i) => [
                $name => BudgetCategory::create([
                    'wedding_id' => $wedding->id,
                    'name' => $name,
                    'sort_order' => $i,
                ])->id,
            ]);

        // [name, category, estimated$, actual$ (null = TBD), paid$]
        $items = [
            ['Reception hall', 'Venue', 12000, 12500, 5000],
            ['Three-course dinner', 'Catering', 9000, null, 0],
            ['Wedding dress', 'Attire', 2500, 2200, 2200],
            ['Suit & accessories', 'Attire', 1200, null, 0],
            ['Ceremony florals', 'Flowers', 1800, 1750, 500],
            ['Photographer (8h)', 'Photography', 3500, 3500, 1000],
        ];

        foreach ($items as [$name, $category, $estimated, $actual, $paid]) {
            BudgetItem::create([
                'wedding_id' => $wedding->id,
                'category_id' => $categories[$category],
                'name' => $name,
                'estimated_cents' => $estimated * 100,
                'actual_cents' => $actual !== null ? $actual * 100 : null,
                'paid_cents' => $paid * 100,
            ]);
        }
    }

    /** A handful of vendors across the booking lifecycle. */
    protected function seedVendors(Wedding $wedding): void
    {
        // [name, category, status, contact, $cost (null = no quote yet), $paid]
        $vendors = [
            ['The Grand Conservatory', VendorCategory::Venue, VendorStatus::Booked, 'Olivia Pearce', 12000, 5000],
            ['Saffron & Sage Catering', VendorCategory::Catering, VendorStatus::Booked, 'Marcus Bell', 9000, 0],
            ['Aperture Studios', VendorCategory::Photography, VendorStatus::Quoted, 'Nina Castillo', 3500, 0],
            ['Wildflower Collective', VendorCategory::Florist, VendorStatus::Contacted, 'Priya Anand', null, 0],
            ['The Velvet Trio', VendorCategory::Music, VendorStatus::Researching, null, null, 0],
            ['Sweet Layers Bakery', VendorCategory::Bakery, VendorStatus::Declined, 'Hannah Cole', 800, 0],
        ];

        foreach ($vendors as [$name, $category, $status, $contact, $cost, $paid]) {
            Vendor::create([
                'wedding_id' => $wedding->id,
                'name' => $name,
                'category' => $category,
                'status' => $status,
                'contact_name' => $contact,
                'cost_cents' => $cost !== null ? $cost * 100 : null,
                'paid_cents' => $paid * 100,
            ]);
        }
    }

    /** A starter checklist spanning the planning timeline. */
    protected function seedChecklist(Wedding $wedding, User $owner): void
    {
        // [title, category, priority, due (months from now, null = no date), complete]
        $tasks = [
            ['Set the wedding date', TaskCategory::Planning, TaskPriority::High, -3, true],
            ['Book the venue', TaskCategory::Logistics, TaskPriority::High, -2, true],
            ['Send save-the-dates', TaskCategory::Stationery, TaskPriority::Medium, -1, false],
            ['Order wedding attire', TaskCategory::Attire, TaskPriority::Medium, 1, false],
            ['Finalize the guest list', TaskCategory::Planning, TaskPriority::High, 2, false],
            ['Plan the ceremony order', TaskCategory::Ceremony, TaskPriority::Medium, 4, false],
            ['Confirm reception menu', TaskCategory::Reception, TaskPriority::Low, 5, false],
        ];

        foreach ($tasks as [$title, $category, $priority, $due, $complete]) {
            Task::create([
                'wedding_id' => $wedding->id,
                'assigned_to' => $owner->id,
                'title' => $title,
                'category' => $category,
                'priority' => $priority,
                'due_date' => $due !== null ? now()->addMonths($due)->toDateString() : null,
                'is_complete' => $complete,
                'completed_at' => $complete ? now() : null,
            ]);
        }
    }
}
