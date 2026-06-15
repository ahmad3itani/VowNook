<?php

namespace Database\Seeders;

use App\Enums\AgeGroup;
use App\Enums\CrewRole;
use App\Enums\EventType;
use App\Enums\GuestSide;
use App\Enums\InspirationCategory;
use App\Enums\Role;
use App\Enums\RsvpStatus;
use App\Enums\SeatingElementType;
use App\Enums\TableShape;
use App\Enums\TaskCategory;
use App\Enums\TaskPriority;
use App\Enums\VendorCategory;
use App\Enums\VendorStatus;
use App\Models\BudgetCategory;
use App\Models\BudgetItem;
use App\Models\CrewMember;
use App\Models\Guest;
use App\Models\GuestGroup;
use App\Models\InspirationItem;
use App\Models\SeatingElement;
use App\Models\SeatingTable;
use App\Models\Task;
use App\Models\TimelineEvent;
use App\Models\Translation;
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
            'name' => 'VowNook Admin',
            'email' => 'admin@vownook.test',
        ]);

        // The couple.
        $owner = User::factory()->plan('planner')->create([
            'name' => 'Amelia Hart',
            'email' => 'couple@vownook.test',
        ]);

        $partner = User::factory()->create([
            'name' => 'Julian Reyes',
            'email' => 'partner@vownook.test',
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
        $this->seedTimeline($wedding);
        $this->seedSeating($wedding);
        $this->seedInspiration($wedding);
        $this->seedCrew($wedding);
        $this->seedWebsite($wedding);
        $this->seedTranslations();

        // Starter SEO blog content.
        $this->call(BlogPostSeeder::class);

        // Launch promo code(s).
        $this->call(PromoCodeSeeder::class);
    }

    /** A few French overrides so the localisation tool has sample data. */
    protected function seedTranslations(): void
    {
        $fr = [
            'app.tagline' => 'Un mariage, composé.',
            'dashboard.welcome' => 'Bon retour',
            'cta.rsvp' => 'RSVP',
            'cta.find_seat' => 'Trouvez votre place',
            'public.rsvp_heading' => 'Merci de répondre',
            'public.rsvp_subheading' => 'Trouvez votre nom pour répondre.',
            'public.seat_heading' => 'Trouvez votre place',
            'public.footer' => 'Réalisé avec VowNook',
        ];

        foreach ($fr as $key => $value) {
            Translation::put('fr', $key, $value);
        }
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
        // [name, category, status, contact, $cost (null = no quote yet), $paid, rating, price_level]
        $vendors = [
            ['The Grand Conservatory', VendorCategory::Venue, VendorStatus::Booked, 'Olivia Pearce', 12000, 5000, 5, 4],
            ['Saffron & Sage Catering', VendorCategory::Catering, VendorStatus::Booked, 'Marcus Bell', 9000, 0, 4, 3],
            // Photography — two to compare.
            ['Aperture Studios', VendorCategory::Photography, VendorStatus::Quoted, 'Nina Castillo', 3500, 0, 5, 4],
            ['Lumière Photography', VendorCategory::Photography, VendorStatus::Contacted, 'Théo Marchand', 2600, 0, 4, 2],
            // Florists — three to compare (Petal & Stem is the best value).
            ['Maison de Fleurs', VendorCategory::Florist, VendorStatus::Quoted, 'Claire Dubois', 4200, 0, 5, 4],
            ['Petal & Stem', VendorCategory::Florist, VendorStatus::Contacted, 'Priya Anand', 2400, 0, 4, 2],
            ['Botanical Soul', VendorCategory::Florist, VendorStatus::Researching, 'Giulia Romano', 3300, 0, 4, 3],
            ['The Velvet Trio', VendorCategory::Music, VendorStatus::Researching, null, null, 0, null, null],
            ['Sweet Layers Bakery', VendorCategory::Bakery, VendorStatus::Declined, 'Hannah Cole', 800, 0, 3, 2],
        ];

        foreach ($vendors as [$name, $category, $status, $contact, $cost, $paid, $rating, $priceLevel]) {
            Vendor::create([
                'wedding_id' => $wedding->id,
                'name' => $name,
                'category' => $category,
                'status' => $status,
                'contact_name' => $contact,
                'rating' => $rating,
                'price_level' => $priceLevel,
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

    /** A sample wedding-day run-of-show, linked to seeded vendors. */
    protected function seedTimeline(Wedding $wedding): void
    {
        $vendorFor = fn (VendorCategory $category) => $wedding->vendors()
            ->where('category', $category->value)->value('id');

        $day = $wedding->event_date?->copy() ?? now()->addMonths(8);

        // [title, type, hour, minute, durationMinutes (null = no end), vendorCategory]
        $events = [
            ['Hair & makeup', EventType::Preparation, 9, 0, 150, null],
            ['First look photos', EventType::Photos, 13, 0, 45, VendorCategory::Photography],
            ['Ceremony', EventType::Ceremony, 15, 0, 45, VendorCategory::Venue],
            ['Cocktail hour', EventType::Cocktails, 16, 0, 60, VendorCategory::Catering],
            ['Reception & dinner', EventType::Reception, 17, 30, 120, VendorCategory::Catering],
            ['First dance & party', EventType::Party, 20, 0, null, VendorCategory::Music],
        ];

        foreach ($events as [$title, $type, $hour, $minute, $duration, $category]) {
            $start = $day->copy()->setTime($hour, $minute);

            TimelineEvent::create([
                'wedding_id' => $wedding->id,
                'vendor_id' => $category !== null ? $vendorFor($category) : null,
                'title' => $title,
                'type' => $type,
                'starts_at' => $start,
                'ends_at' => $duration !== null ? $start->copy()->addMinutes($duration) : null,
                'location' => 'The Grand Conservatory',
            ]);
        }
    }

    /** A small floor plan with a couple of attending guests already seated. */
    protected function seedSeating(Wedding $wedding): void
    {
        // [name, shape, capacity, x%, y%]
        $layout = [
            ['Head Table', TableShape::Rectangle, 6, 50, 20],
            ['Table 1', TableShape::Round, 8, 25, 55],
            ['Table 2', TableShape::Round, 8, 50, 70],
            ['Table 3', TableShape::Round, 8, 75, 55],
        ];

        $tables = [];
        foreach ($layout as [$name, $shape, $capacity, $x, $y]) {
            $tables[] = SeatingTable::create([
                'wedding_id' => $wedding->id,
                'name' => $name,
                'shape' => $shape,
                'capacity' => $capacity,
                'position_x' => $x,
                'position_y' => $y,
            ]);
        }

        // Seat the first few attending guests at Table 1 (in specific chairs).
        $wedding->guests()
            ->where('rsvp_status', RsvpStatus::Attending->value)
            ->take(3)
            ->get()
            ->values()
            ->each(fn (Guest $guest, int $i) => $guest->update([
                'table_id' => $tables[1]->id,
                'seat_number' => $i + 1,
            ]));

        // Room dimensions for the floor plan.
        $wedding->seatingLayout()->create(['room_width' => 44, 'room_height' => 32]);

        // A few non-table elements on the floor.
        // [type, x%, y%, w%, h%]
        $elements = [
            [SeatingElementType::DanceFloor, 50, 45, 28, 24],
            [SeatingElementType::Stage, 50, 8, 24, 10],
            [SeatingElementType::Bar, 12, 85, 22, 8],
            [SeatingElementType::DjBooth, 85, 15, 12, 10],
            [SeatingElementType::GiftTable, 88, 85, 12, 7],
        ];

        foreach ($elements as [$type, $x, $y, $w, $h]) {
            SeatingElement::create([
                'wedding_id' => $wedding->id,
                'type' => $type,
                'position_x' => $x,
                'position_y' => $y,
                'width' => $w,
                'height' => $h,
            ]);
        }
    }

    protected function seedInspiration(Wedding $wedding): void
    {
        // [title, category]
        $ideas = [
            ['Blush peony centrepieces', InspirationCategory::Flowers],
            ['Garden ceremony arch', InspirationCategory::Decor],
            ['Candlelit reception tables', InspirationCategory::Decor],
            ['Classic three-tier cake', InspirationCategory::Cake],
            ['Lace-back wedding gown', InspirationCategory::Attire],
            ['Letterpress invitation suite', InspirationCategory::Stationery],
        ];

        foreach ($ideas as [$title, $category]) {
            InspirationItem::create([
                'wedding_id' => $wedding->id,
                'title' => $title,
                'category' => $category,
                'image_url' => null,
                'link_url' => null,
            ]);
        }
    }

    protected function seedCrew(Wedding $wedding): void
    {
        // [name, role]
        $crew = [
            ['Sophie Tremblay', CrewRole::MaidOfHonour],
            ['Daniel Roy', CrewRole::BestMan],
            ['Olivia Gagnon', CrewRole::Bridesmaid],
            ['Liam Bouchard', CrewRole::Groomsman],
            ['Rev. Marie Lefebvre', CrewRole::Officiant],
            ['Marc Pelletier', CrewRole::Host],
        ];

        foreach ($crew as [$name, $role]) {
            CrewMember::create([
                'wedding_id' => $wedding->id,
                'name' => $name,
                'role' => $role,
            ]);
        }
    }

    protected function seedWebsite(Wedding $wedding): void
    {
        $wedding->website()->create([
            'is_published' => true,
            'headline' => 'Together with their families',
            'welcome_message' => 'We are so excited to celebrate our wedding day with the people we love most. Thank you for being part of our story.',
            'our_story' => "We met one rainy autumn evening and have been inseparable ever since. After eight wonderful years, a hike, and one very nervous proposal at the lookout, we can't wait to say \"I do\".",
            'venue_name' => 'Rosewood Estate',
            'venue_address' => '1200 Orchard Lane, Mont-Tremblant, QC',
            'ceremony_time' => '4:00 PM',
            'dress_code' => 'Garden formal',
        ]);
    }
}
